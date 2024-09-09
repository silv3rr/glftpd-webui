<?php

if (class_exists('PHPUnit\\Runner\\Version') && version_compare(PHPUnit\Runner\Version::id(), '9') >= 0) {
    class_alias('IPLib\\Test\\TestCase9', 'IPLib\\Test\\TestCase');
} elseif (class_exists('PHPUnit\\Runner\\Version') && version_compare(PHPUnit\Runner\Version::id(), '7') >= 0) {
    class_alias('IPLib\\Test\\TestCase7', 'IPLib\\Test\\TestCase');
} else {
    class_alias('IPLib\\Test\\TestCase4', 'IPLib\\Test\\TestCase');
}
