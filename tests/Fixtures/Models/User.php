<?php

namespace Ringierimu\StateWorkflow\Tests\Fixtures\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Ringierimu\StateWorkflow\Traits\HasWorkflowTrait;

/**
 * Class User.
 */
class User extends Authenticatable
{
    use HasWorkflowTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'user_state',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
