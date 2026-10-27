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
- `$sql` the SQL query starting by _"CREATE TABLE"_ or a path to an existing .sql file. `dbDelta()` will be used to manage upgrades
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