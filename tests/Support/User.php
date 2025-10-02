<?php
// tests/Support/User.php
namespace PujaNaik\UserDiscount\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $guarded = [];
}
