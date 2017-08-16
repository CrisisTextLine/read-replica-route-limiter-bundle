[![Build Status](https://travis-ci.org/cjcodes/slave-route-limiter-bundle.svg?branch=master)](https://travis-ci.org/cjcodes/slave-route-limiter-bundle)

# Slave Router Limiter Bundle

This bundle, upon installation, will prevent [MasterSlaveConnection](http://www.doctrine-project.org/api/dbal/2.3/class-Doctrine.DBAL.Connections.MasterSlaveConnection.html) from doing its magic on every single route in your application.

Instead, to enable the slave database, add a @ShouldUseSlave annotation to your controller class or method.

If your route ends up doing any sort of write to the database, note that MasterSlaveConnection will automatically detect this and promote the connection to use the master database. (See the documentation of MasterSlaveConnection.)

**IMPORTANT NOTE:**

The intent of this bundle is NOT to guarantee that all queries go to your slave. Instead, it allows you greater choice of where MasterSlaveConnection can do its magic.

For instance, if you are considering turning on MasterSlaveConnection on a very large site and are concerned about the impact it may have on your many routes, use this bundle to limit the potential impact of that change to only a few routes of your choosing. All other routes will be guaranteed to use master.

## Available annotations

- `@ShouldUseSlave`: Allows the slave database to be used for this route or all routes within a controller it's applied to.
- `@ShouldNotUseSlave`: If `@ShouldUseSlave` is applied to a Controller class, this annotation will prevent the slave from being used on the method this is applied to.

## Basic Usage

In this example, this route (and only this route in our app) will have the option of using the slave database. All other routes that do not have this annotation will use the master 100% of the time, every time.

```php
use CJCodes\SlaveRouteLimiterBundle\Annotation\ShouldUseSlave;
...

class MyController extends Controller
{
  /**
   * @Route('/')
   * @ShouldUseSlave
   */
  function indexAction()
  {
    ...
  }
}

```

## Advanced Usage

### Applying to all routes within a Controller

The example below will enable the slave database on all routes inside of the MyController controller.

```php
use CJCodes\SlaveRouteLimiterBundle\Annotation\ShouldUseSlave;
...

/**
 * @Route('/subsection')
 * @ShouldUseSlave
 */
class MyController extends Controller
{
  /**
   * @Route('/')
   */
  function indexAction()
  {
    ...
  }
}

```

### Applying to all routes within a Controller...except one

This example applies the slave database to all methods except the `excludeAction()`.

```php
use CJCodes\SlaveRouteLimiterBundle\Annotation\ShouldUseSlave;
use CJCodes\SlaveRouteLimiterBundle\Annotation\ShouldNotUseSlave;

...

/**
 * @Route('/subsection')
 * @ShouldUseSlave
 */
class MyController extends Controller
{
  /**
   * @Route('/')
   */
  function indexAction()
  {
    ...
  }

  /**
   * @Route('/exclude')
   * @ShouldNotUseSlave
   */
  function excludeAction()
  {
    ...
  }
}

```
