uploader
========

<p>A Symfony project created on May 11, 2016, 5:05 pm.</p>

<b>to create tables in db:</b>
<p>$ php app/console doctrine:schema:update --force</p>

<b>to run rabbitmq (required for ftp uploading)</b>
<p>$ ./app/console rabbitmq:consumer upload_file</p>

<b>to reset rabitmq:</b>

<p>$ sudo rabbitmqctl stop_app</p>
<p>$ sudo rabbitmqctl reset</p>
<p>$ sudo rabbitmqctl start_app</p>

<b>to clear cache:</b>

<p>$ php app/console cache:clear</p>