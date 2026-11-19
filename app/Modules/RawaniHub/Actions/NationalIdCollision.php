<?php

namespace App\Modules\RawaniHub\Actions;

/** A national ID/email already belongs to another user; self-service must stop and contact support. */
final class NationalIdCollision extends \DomainException {}
