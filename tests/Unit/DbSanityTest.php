<?php
// it('uses sqlite in-memory', function () {
//     $conn = app('db')->connection();
//     expect($conn->getDriverName())->toBe('sqlite');

//     // discounts table should exist from package migrations
//     $tables = $conn->select("SELECT name FROM sqlite_master WHERE type='table'");
//     $names = array_map(fn($r) => $r->name, $tables);
//     expect($names)->toContain('discounts', 'user_discounts', 'discount_audits');
// });

// it('has a working test DB with package tables', function () {
//     $conn = app('db')->connection();
//     $driver = $conn->getDriverName();
// echo $driver;
//     // Assert driver is either sqlite (in-memory) or mysql (XAMPP)
//     expect(in_array($driver, ['sqlite','mysql']))->toBeTrue();

//     // Use Schema builder so it works on any driver
//     $schema = $conn->getSchemaBuilder();

//     expect($schema->hasTable('discounts'))->toBeTrue();
//     expect($schema->hasTable('user_discounts'))->toBeTrue();
//     expect($schema->hasTable('discount_audits'))->toBeTrue();
// });


it('has a working sqlite testing DB with package tables', function () {
    $conn   = app('db')->connection('testing');
    $driver = $conn->getDriverName();

    expect($driver)->toBe('sqlite');

    $schema = $conn->getSchemaBuilder();
    expect($schema->hasTable('users'))->toBeTrue();
    expect($schema->hasTable('discounts'))->toBeTrue();
    expect($schema->hasTable('user_discounts'))->toBeTrue();
    expect($schema->hasTable('discount_audits'))->toBeTrue();
});
