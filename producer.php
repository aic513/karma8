<?php

$mysqli = new mysqli("localhost", "username", "password", "database");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$offset = 0;
$max_attempts = 3; // Максимальное количество попыток
$batch_size = 10000; // Размер пакета для постраничной выборки


// Функция для добавления задач в очередь
function enqueue_tasks($mysqli, $offset, $max_attempts, $batch_size)
{
    $attempts = 0;

    while ($attempts < $max_attempts) {
        $mysqli->begin_transaction();

        $query = "SELECT username, email, validts FROM users 
              WHERE validts > 0 
                AND (validts BETWEEN UNIX_TIMESTAMP(CURDATE() + INTERVAL 1 DAY) AND UNIX_TIMESTAMP(CURDATE() + INTERVAL 1 DAY) + 86399 
                  OR validts BETWEEN UNIX_TIMESTAMP(CURDATE() + INTERVAL 3 DAY) AND UNIX_TIMESTAMP(CURDATE() + INTERVAL 3 DAY) + 86399) 
                AND confirmed = 1 
                AND checked = 1 
                AND valid = 1
              LIMIT {$batch_size} OFFSET {$offset}";

        $result = $mysqli->query($query);

        if ($result->num_rows > 0) {
            try {
                while ($row = $result->fetch_assoc()) {
                    $username = $row['username'];
                    $email = $row['email'];
                    $validts = $row['validts'];

                    $stmt = $mysqli->prepare("INSERT INTO task_queue (email, username, validts) VALUES (?, ?, ?)");
                    $stmt->bind_param('ssi', $email, $username, $validts);
                    $stmt->execute();
                }
                $mysqli->commit();
            } catch (Exception $e) {
                $mysqli->rollback();
                $attempts++;
                error_log("Error enqueuing tasks: " . $e->getMessage() . ", attempt $attempts.");
            }
            return true;
        } else {
            return false; // Нет задач для обработки
        }
    }

    return false; // Если все попытки не удались
}

function run_producer($mysqli, $max_attempts, $batch_size)
{
    $offset = 0;
    while (true) {
        $success = enqueue_tasks($mysqli, $offset, $max_attempts, $batch_size);
        if ($success) {
            $offset += $batch_size;
        } else {
            sleep(10);
            $offset = 0;
        }
    }
}

run_producer($mysqli, $max_attempts, $batch_size);

$mysqli->close();
