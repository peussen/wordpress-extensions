# loop
(since 0.5.2)
Loop over an iteratable object like an array, wp_query or Iterator object

## Description
```php
loop($queryOrArray): \Iterator
```
**loop** will return the iterator object used to loop over the data. This iterator will comply with the 
`Woppe\Wordpress\Iterator\TemplateIteratorInterface`.

## Parameters
**$queryOrArray**: Either an array, an instance of \WP_Query or an Object that implements the `Iterator` interface.
   
## Return Values
Depending on the type of object that was passed in it will return a different instance of a 
`Woppe\Wordpress\Iterator\TemplateIteratorInterface` compliant class.
    
## Examples        
**Example 1: loop over an array**
```php
<?php

$items = [ 'one', 'two', 'three' ];

loop($items)->each(function($key,$value) {
  echo "Item: " . $key . " is " . $value . "\n";
});
```

**Example 2: loop over an array and apply a template**
When applying template, you will get two variables `$loop_entry` which contains the value of the
current item, and `$loop_position` which contains the key/position of the value.

```php
<?php

$items = [ 'one', 'two', 'three' ];
loop($items)->apply('templates/content','loop');
```

**Example 3: Loop over a WP_Query instance**
```php
<?php
$query = new \Wp_Query($args);

loop($query)->apply('templates/content','row');

```
## See also
 * [TemplateIteratorInterface](../Iterator/TemplateIteratorInterface.md)