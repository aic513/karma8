Этот проект реализует сервис для рассылки уведомлений пользователям об истекающих подписках.
Пользователи уведомляются по электронной почте за один и за три дня до окончания их подписки.
Проект включает скрипты для создания и потребления сообщений, а также управления базой данных
и отправки электронных писем.

## Клонирование репозитория:
git clone <repository_url>
cd <repository_directory>

## Настройка бд:
mysql -u <username> -p <database_name> < dump.sql

Обновите данные подключения к базе данных в producer.php и consumer.php.



1. Скрипт producer.php проверяет базу данных на наличие пользователей,
у которых подписка истекает через один или три дня, и добавляет сообщения для этих пользователей в очередь.

2. Скрипт consumer.php обрабатывает очередь, проверяет валидность электронной почты с помощью заглушечной функции и отправляет уведомления на валидные адреса электронной почты.


3. Скрипт cron_producer задания для регулярного запуска скрипта producer.php.


4. Скрипт cron_consumer задания для регулярного запуска скрипта consumer.php.




