# Kohana Filtration

Filtration helper for Kohana Framework.

## Usage

```php
$data = array(
    'name' => 'John',
    'surname' = 'Doe',
    'email' => 'jdoe@domain.com',
    'phone' => '999 999 999',
);

$result = Filtration::factory($data)
    ->rule('email', 'email')
    ->rule('phone', 'phone')
    ->execute();

```