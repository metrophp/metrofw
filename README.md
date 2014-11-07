Metro Framework
======
MSE (modular service event framework)


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


Sample Configuration for Nofw (the no framework framework)
=====
```php
associate_iCanHandle('analyze',  'metrofw/analyzer.php');
associate_iCanHandle('analyze',  'metrofw/router.php');
associate_iCanHandle('resources', 'metrofw/utils.php');
associate_iCanHandle('output', 'metrofw/output.php');
associate_iCanHandle('exception', 'metrofw/exdump.php::onException');

associate_iAmA('request',  'metrofw/request.php');
associate_iAmA('response', 'metrofw/response.php');
associate_iAmA('router',   'metrofw/router.php');

associate_set('template_basedir', 'templates/');
associate_set('template_baseuri', 'templates/');
associate_set('template_name', 'webapp01');

associate_set('route_rules', 
	array_merge(array('/:appName'=>array( 'modName'=>'main', 'actName'=>'main' )),
	associate_get('route_rules')));

associate_set('route_rules', 
	array_merge(array('/:appName/:modName'=>array( 'actName'=>'main' )),
	associate_get('route_rules')));

associate_set('route_rules', 
	array_merge(array('/:appName/:modName/:actName'=>array(  )),
	associate_get('route_rules')));

associate_set('route_rules', 
	array_merge(array('/:appName/:modName/:actName/:arg'=>array(  )),
	associate_get('route_rules')));
```

