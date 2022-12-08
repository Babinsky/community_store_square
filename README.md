## Project
community_store_square
Square credit card payment add-on for Community Store for concrete5

https://squareup.com

## Installation
Install Community Store first
Register your application with square to generate Application ID / Token / Location (https://docs.connect.squareup.com/)
Install this package

## Errata
vendor/apimatic/jsonmapper/src/JsonMapper.php
May throw an exception on line 119 on some hosting configurations. Simply commenting it out seems to resolve the issue.
//$this->config = parse_ini_file($iniPath);

## Contributors
Thanks both Mirko (bulli1979) and Christian (guedeWebGate) for their help in developing this add-on.
Updated to latest API version by Jeff Paetkau Dec 2022
Also, I would like to thank Ryan (Mesuva) for the review.
Thanks guys for your input and guidance!
