<?php

namespace App\Exceptions;

use DomainException;

/** Business-rule failure whose message is safe (and written) to show to the customer. */
class StoreException extends DomainException {}
