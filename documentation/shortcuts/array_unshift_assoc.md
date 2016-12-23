# array_unshift_assoc
(since 0.5.2)

Adds a key/value pair at the beginning of an associative array.

Sometimes you have an associative array which has a specific order,
and you would like to add an item at the beginning of the array. This 
function will help you do that.

## Description

```php
array array_unshift_assoc(array $array, $key, $value)
```

## Parameters

**$array**: the array you want to mutate

**$key**: The key that should be added at the front of the array

**$value**: The value the key should have

## Return values
The method will return a new array containing the extra key as first item.

