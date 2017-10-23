<?php

/*
Template showing the usage of class mapping to data.
Keys should be exactly the way they are coming in the $request['info']['group'].
If a match is found the request will be handled by the paired class.
*/

return [
    // Group 'accounts' is handled by Account class.
    'accounts' => 'Account',
    // Group 'grouplist' is handled by GroupManager class.
    'grouplist' => 'GroupManager'
    /// etc...
];
