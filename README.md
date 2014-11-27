Metro Framework
======
Libraries for fast websites.  Metrofw is a small, lazy loading, dependency injection, event based lifecycle management kernel.
These metrofw libraries will give you things like a router and template parsing.  Relies on Metrodi\_Container for lazy
dependency injection.


Installation
=======
Use metrophp/metroproject to get a base file strucutre and composer dependency.
```
  git clone https://github.com/metrophp/metroproject.git
  cd metroproject.git
  rm -rf .git/
  composer install
  git init .
```

File layout
----

```
 [nofw root]
 src/
    mod_a/
         main.php
    mod_b/
         main.php
         lib/
             model_b.php
```

URL structure
----

    A url like /mod_a/ will map to
    src/mod_a/main.php
    and call function
    Mod_a_Main::mainAction(request, resposne)

**main** is the default keyword used everywhere when a value is absent from the URL.  (index is for directory listing)

####MSE
Metro uses Modules, Services, and Events
```
 http://tld.ext/module/service/event

```

Which maps to:
```php
 src/module/service.php
 class Module_Service
 public function eventAction()
```
Template and output
----
In your template, add 
```php
<?php echo Metrofw_Template::parseSection('main'); ?>
```
In your code (src/main/main.php) add
```php
public function mainAction(request, response) {
    $response->main = array('Hello,');
    $response->addTo('main', 'World');
}
```

### Rendering objects

Any object added to the response will have its toHTML() method called by the template library

### Partial template files
If you are parsing the "main" section, and there is nothing added to your response with the "main" keyword, the template system will look for a partial template file under your module's **view** directory
```
 [nofw root]
 src/
    mod_a/
         main.php
         views/
               main_main.html.php
```
The file naming is *service _ event .html.php*  The service and event are the second 2 parts of the URL after the module


Sample Bootstrap file to work with Metro DI (metrophp/metrodi)
=====
```php
_iCanHandle('analyze',   'metrofw/analyzer.php');
_iCanHandle('analyze',   'metrofw/router.php', 3);
_iCanHandle('resources', 'metrofw/utils.php');
_iCanHandle('output',    'metrofw/output.php');
//will be removed if output.php doesn't think we need HTML output
_iCanHandle('output',    'metrofw/template.php', 3);
_iCanHandle('exception', 'metrofw/exdump.php::onException');
_iCanHandle('hangup',    'metrofw/output.php');

_didef('request',        'metrofw/request.php');
_didef('response',       'metrofw/response.php');
_didef('router',         'metrofw/router.php');

_set('template_basedir', 'templates/');
_set('template_baseuri', 'templates/');
_set('template_name',    'webapp01');

_set('route_rules', array());

_set('route_rules', 
	array_merge(array('/:appName'=>array( 'modName'=>'main', 'actName'=>'main' )),
	_get('route_rules')));

_set('route_rules', 
	array_merge(array('/:appName/:modName'=>array( 'actName'=>'main' )),
	_get('route_rules')));

_set('route_rules', 
	array_merge(array('/:appName/:modName/:actName'=>array(  )),
	_get('route_rules')));

_set('route_rules', 
	array_merge(array('/:appName/:modName/:actName/:arg'=>array(  )),
	_get('route_rules')));
```

