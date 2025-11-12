<?php

require "email_validator.php";

function assertEqual($expected, $actual, $testName, $input) {
    if ($expected === $actual) {
        echo "[PASS] $testName Input: $input\n";
    } else {
        echo "[FAIL] $testName Expected: $expected Got: $actual\n";
    }
}

assertEqual(true, validateIdentifier("wanxiang@buffalo.edu"), "Valid Email 1","wanxiang@buffalo.edu");
assertEqual(true, validateIdentifier("onehundred@gmail.com"), "Valid Email2", "onehundred@gmail.com");
assertEqual(true, validateIdentifier("myemail02@yahoo.com"), "Valid Email3","myemail02@yahoo.com");
assertEqual(true, validateIdentifier("user.name+tag@example.co.uk"), "Valid Email 4", "user.name+tag@example.co.uk");
assertEqual(true, validateIdentifier("+bob@example.com"), "Valid Email 5", "+bob@example.com");
assertEqual(true, validateIdentifier("_alice@example.com"), "Valid Email 6", "_alice@example.com");
assertEqual(true, validateIdentifier("12345@example.com"), "Valid Email 7", "12345@example.com");
assertEqual(true, validateIdentifier("x@gmail.com"), "Valid Email 8", "x@gmail.com");

assertEqual(false, validateIdentifier("wanxiang"), "No @", "wanxiang");
assertEqual(false, validateIdentifier(".myemail@gmail.com"), "Dot At Front", ".myemail@gmail.com");
assertEqual(false, validateIdentifier("user..name@example.com"), "Two Dots", "user..name@example.com");
assertEqual(false, validateIdentifier("user@@example.com"), "Two @", "user@@example.com");
assertEqual(false, validateIdentifier("user@example"), "No TLD", "user@example");
assertEqual(false, validateIdentifier("@example.com"), "No domain", "@example.com");

assertEqual(true, validateIdentifier("3447129090"), "Valid Phone Number 1", "3447129090");
assertEqual(true, validateIdentifier("1234567"), "Valid Phone Number 2", "1234567");
assertEqual(true, validateIdentifier("12345678"), "Valid Phone Number 3", "12345678");
assertEqual(true, validateIdentifier("+123456789"), "Valid Phone Number 4", "+123456789");
assertEqual(true, validateIdentifier("+14155552671"), "Valid Phone Number 5", "+14155552671");
assertEqual(true, validateIdentifier("987654321012345"), "Valid Phone Number 6", "987654321012345");
assertEqual(true, validateIdentifier("+441234567890"), "Valid Phone Number 7", "+441234567890");
assertEqual(true, validateIdentifier("123456789012345"), "Valid Phone Number 8", "123456789012345");

assertEqual(false, validateIdentifier("123456"), "Too Short", "123456");
assertEqual(false, validateIdentifier("1234567890123456"), "Too Long", "1234567890123456");
assertEqual(false, validateIdentifier("++1234567"), "Double +", "++1234567");
assertEqual(false, validateIdentifier("+12-3456789"), "Contains Invalid -", "+12-3456789");
assertEqual(false, validateIdentifier("+12 3456789"), "Contains Space", "+12 3456789");
assertEqual(false, validateIdentifier("abcd1234"), "Contains Letters", "abcd1234");
assertEqual(false, validateIdentifier("!1234567"), "Special Character At The Start", "!1234567");
assertEqual(false, validateIdentifier("1234.5678"), "Dot Inside Number", "1234.5678");







?>