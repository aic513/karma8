<?php

$mysqli = new mysqli("localhost", "username", "password", "database");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Функция для обработки задач из очереди
function process_task_queue($mysqli)
{
    $mysqli->begin_transaction();

    // Выборка задачи с состоянием 'pending' и блокировка строки
    $query = "SELECT id, email, username, validts FROM task_queue WHERE status = 'pending' LIMIT 1 FOR UPDATE SKIP LOCKED";
    $result = $mysqli->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $task_id = $row['id'];
        $username = $row['username'];
        $email = $row['email'];

        try {
            // Обновление статуса задачи на 'processing'
            $mysqli->query("UPDATE task_queue SET status = 'processing' WHERE id = {$task_id}");

            // Проверка, отправляли ли мы уже уведомление этому пользователю
            $notification_check = $mysqli->query("SELECT * FROM notifications WHERE email='{$email}' AND notification_date=CURDATE()");
            if ($notification_check->num_rows == 0) {
                // Отправка email
                $text = "{$username}, your subscription is expiring soon";
                $send_status = send_email('no-reply@example.com', $email, $text);

                if ($send_status) {
                    $mysqli->query("INSERT INTO notifications (email, notification_date) VALUES ('{$email}', CURDATE())");

                    $mysqli->query("UPDATE task_queue SET status = 'completed' WHERE id = {$task_id}");
                } else {
                    error_log("Failed to send email to $email");

                    $mysqli->query("UPDATE task_queue SET status = 'failed' WHERE id = {$task_id}");
                }
            } else {
                $mysqli->query("UPDATE task_queue SET status = 'completed' WHERE id = {$task_id}");
            }

            $mysqli->commit();
        } catch (Exception $e) {
            error_log($e->getMessage());
            $mysqli->query("UPDATE task_queue SET status = 'failed' WHERE id = {$task_id}");
            $mysqli->commit();
        }
    } else {
        // Завершение транзакции, если нет задач для обработки
        $mysqli->commit();
    }
}

// Постоянная обработка задач из очереди
while (true) {
    process_task_queue($mysqli);
    // Пауза на 1 секунду перед следующим циклом
    sleep(1);
}

$mysqli->close();

// Функция-заглушка для отправки email
function send_email($from, $to, $text)
{
    sleep(rand(1, 10));
    // Эмулируем успешную отправку (75% успеха)
    return rand(0, 3) > 0;
}
