# array_value
(Since 0.5.2)
 
Obtains a specific key from an array, if it's there. Or return a default if the key is not there.
 
## Description

```php
 mixed array_value($array,$key,$default = null);
```

## Parameters
**$array**: the array you want to get a value from

**$key**: the key you want to fetch

**$default**: the value that should be returned if the key was not found

## Return values
The return value depends on the input array and default value you pass along.

## Examples
**Get a value from a numeric array**
```php
$items = ['one','two','three'];

echo array_value($items,1);  // Will echo 'two'
```

**Get a value from an associative array**
```php
$items = ['one' => 'yes', 'two' => 'no', 'three' => 'maybe'];

echo array_value($items,'one'); // will echo 'yes'
```

**Make use of the default to always get a proper value**
```php
$items = ['one' => 'yes', 'two' => 'no'];

echo array_value($items,'three','maybe'); // will echo 'maybe'
```
