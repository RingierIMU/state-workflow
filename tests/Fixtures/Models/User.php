<?php

namespace Ringierimu\StateWorkflow\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Ringierimu\StateWorkflow\Tests\Fixtures\Database\Factories\UserFactory as UserFactoryAlias;
use Ringierimu\StateWorkflow\Traits\HasWorkflowTrait;

/**
 * Class User.
 */
class User extends Authenticatable
{
    use HasFactory;
    use HasWorkflowTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected static function newFactory()
    {
        return UserFactoryAlias::new();
    }
}
