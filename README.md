# WP Ant-IT

Manage an object oriented anthill in WordPress plugins.

An entity manager for custom tables in WordPress plugins.

## Install

```bash
composer require agencenous/wp-ant-it
```

## Configure

The library comes with a **manager** and a **model** classes.

Load the library

```php
require_once __DIR__.'/vendor/autoload.php';
```

For each entity type, declare both classes.

### The manager
Pretty like a _repository_ in Symfony using the `wpdb` engine.

Some attributes have to be declared:

- `$table` the final table name (without any prefix)
- `$prefix` an additional prefix for multiple tables in the same plugin
- `$sql` the path to an existing .sql file. `dbDelta()` will be used to manage upgrades. Use _%TABLE%_ as table name, it will be replaced by the full tablename including prefix.
- `$model` the classname of the related model
- `$attributes` an associative array: shortname => column of each model's property

```php
use AntIT\Manager;

class NotificationsManager extends Manager {
    public $table = 'notifications';
    public $prefix = 'MyPlugin_';
    public $sql = __DIR__ . '/sql/notifications.sql';
    public $model = 'Notification';

    public $attributes = [
      'i'=>'id',
      'd'=>'date',
      'u'=>'user_id',
      'em'=>'email',
      'su'=>'subject',
      'fn'=>'from_name',
      'fm'=>'from_mail',
      'm'=>'message',
      's'=>'status',
    ];
}
```

## SQL file

Use _%TABLE%_ as table name, it will be replaced by the full tablename including prefix.

Increment the version on each change. The system will detect version change and trigger the upgrade.

```sql
-- /*
-- Author: agencenous
-- Version: 11
-- */

CREATE TABLE `%TABLE%` (
   `id` bigint(20) NOT NULL AUTO_INCREMENT,
   `user_id` mediumint(10) NOT NULL DEFAULT '0',
   `date` date NOT NULL DEFAULT '0000-00-00',
   `email` varchar(255) NOT NULL DEFAULT '',
   `subject` varchar(255) NOT NULL DEFAULT '',
   `from_name` varchar(255) NOT NULL DEFAULT '',
   `from_mail` varchar(255) NOT NULL DEFAULT '',
   `message` text NOT NULL DEFAULT '',
   `status` varchar(20) NOT NULL DEFAULT 'pending',
   PRIMARY KEY (`id`),
   INDEX  `DATE` (`date`),
   INDEX  `USER` (`user_id`)
);
```


### The model

like an entity in Symfony

```php
use AntIT\Model;

class Notification extends Model {
    // Declare attributes, according the manager
    public $date;
    public $user_id;
    public $email;
    public $subject;
    public $from_name;
    public $from_mail;
    public $message;
    public $status;

    function __construct($atts=null){
      $this->_manager = new NotificationsManager(); // Or a previously declared instance
      parent::__construct($atts);
    }
}
```

## Usage

Simple query

```php
$NotificationsManager = new NotificationsManager();

$offset = 0;
$per_page = 10;

$filters = [
    'ORDER_BY'=>"`date` DESC",
    'LIMIT'=>"{$offset},{$per_page}",
];

$notifications = $NotificationsManager->get($filters);

foreach ($notifications as $notification){
    // ...
}
```

Object oriented actions

```php
// Write
$notification = new Notification();
$notification->email = 'test@test.te';
$notification->message = 'Some text';
$notifcation->status = 'pending';
$saved_notification_id = $notification->save();

// Read
$read_notification = new Notification($saved_notification_id);
print $read_notification->date;
```