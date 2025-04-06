<?php
$string['bsr_string'] = 'BSR Pairs';
$string['stringsetting'] = 'Bulk String Replacement(BSR)';
$string['active'] = 'Active';
$string['pluginname'] = 'Bulk String Replacement';
$string['replacestrgrp'] = 'Bulk Replacement';
$string['intro'] = "<p>" . 'Provide source and replacement strings as a pair separated by a <b>“:”’</b>,
                   one pair per line. You can specify words or partial words, or a phrase with embedded spaces e.g. ‘Learning Areas’.
                   Since the replacement is exact, the uppercase and lowercase forms of a word need to be replaced using separate entries. Similarly, unless the singular form is a subset of the plural form e.g ‘course’ and ‘courses’, separate entries would be required for them as well e.g. ‘activity’ and ‘activities’.
                   ' . "</p>";
$string['stringreplace_desc'] = 'This is a utility to replace exact strings in a chosen language pack efficiently. The changes are only done in cache and language files / packs are not changed. The following changes are not allowed to prevent confusion:'
        . '<br />' . '1. The target value matches an existing plugin name, entity or type, and is not allowed to prevent confusion.'
        . '<br />' . '2. Cyclical renaming is not allowed within the same language i.e. a member of a pair cannot lie on both sides of the separator in the whole set.'
        . '<br />' . '3. White space on the left or right of the source or replacement string - embedded space is allowed.'.'<br /><br /><br />';
$string['checkbox_info'] = 'You can reset the string to defaults by simply unchecking and submitting the additional check-box below the input box. You can still keep the replacement pairs associated but these will not be effective.';
