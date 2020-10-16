# Yuri (WIP)
[![Maintainability](https://api.codeclimate.com/v1/badges/b2128d5ffa59e0d5265a/maintainability)](https://codeclimate.com/github/kristos80/yuri/maintainability)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=kristos80_yuri&metric=bugs)](https://sonarcloud.io/dashboard?id=kristos80_yuri)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=kristos80_yuri&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=kristos80_yuri)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=kristos80_yuri&metric=vulnerabilities)](https://sonarcloud.io/dashboard?id=kristos80_yuri)
--
`Yuri` is a small utility for making sense out of URIs, without having to write the same utility methods again and again.

By default `Yuri` works for the current URL if an input URI is ommited:
```PHP
<?php
use Kristos80\Yuri\Yuri;

require_once __DIR__ . '/vendor/autoload.php';

$yuri = new Yuri('https://www.dummy.com/path/index.html?q[]=1&q[]=2');
```
You can check, almost, all methods returned values, by using one of the `asArray()`, `asClass()` or `asJsonString()` methods:

```PHP
echo($yuri->asJsonString(TRUE)); 
//@see Yuri::asJsonString(bool $prettyPrint = FALSE)
//Notation syntax is, also, supported
```
```JSON
{
    "originalUri": "https:\/\/www.dummy.com\/path\/index.html?q[]=1&q[]=2",
    "normalizedUri": "https:\/\/www.dummy.com\/path\/index.html?q[0]=1&q[1]=2",
    "normalizedUriWithSlash": "https:\/\/www.dummy.com\/path\/index.html?q[0]=1&q[1]=2",
    "scheme": "https",
    "host": "www.dummy.com",
    "path": "\/path\/index.html",
    "paths": [
        "path",
        "index.html"
    ],
    "query": {
        "q": [
            "1",
            "2"
        ]
    },
    "originalQueryString": "q[]=1&q[]=2",
    "queryString": "q[0]=1&q[1]=2",
    "port": null,
    "uid": "6ec78702bb2686046b5102547fbd3d79",
    "isHttps": true,
    "isFile": true
}
```
And there are some extra utility methods for getting data about `path` and `query`, like:
```PHP
echo $yuri->getQueryVar('q.0','defaultValueIfNotExists'); //@see Yuri::getQueryVar(string $varNotation, $defaultValue = NULL)
```
```
1
```
