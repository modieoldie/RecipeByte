<?php

// Validating email or phone number.
function validateIdentifier(string $identifier): bool {
    $identifier = trim($identifier);

    // See if it is a valid email.
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        return true;
    }

    // See if it is a valid phone number.
    if (preg_match("/^\+?\d{7,15}$/", $identifier)) {
        return true;
    }

    return false;
}

?>