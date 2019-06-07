A simple mapper for your view files, with lazy loading and autocomplete support.

### Install
```bash
composer require hitechcoding/view-mapper
```

### Requirements
PHP7.1

### Why
Let's say you have Doctrine entity like this:

```php
class Order
{
    private $sum;
    
    /** @var ArrayCollection|Product[] */
    private $products;
    
    public function getSum(): float
    {
        return $this->sum;
    }
    
    /** @return Product[] */
    public function getProducts(): array
    {
        return $this->products->toArray();
    }
}
```

If you assign instance of Order to Twig
 
```php

/** @Route("/orders/{id}") */
public function showDetailed(Order $order): Response
{
    return $this->render('orders/detailed.html.twig', [
        'order' => $order,
    ]);
}
``` 
 
template would be something like this:
```twig
<h4>Total: {{ order.sum }}</h4>
<ul>
    {% for product in order.products %}
    <li>{{ product.name }}</li>
    {% endfor %}
</ul>

```

The problem is when you refactor methods ``getSum()`` and ``getProducts()`` to for example: ``getTotal()`` and ``getArticles()``. Your view files with trigger exceptions.

Another problem is both methods will be registered as unused by your IDE or tools that detects them. If you don't have functional tests covering every scenario, you may end with code that really is not used.

### View mapping
This package helps with this problem (it can never fully resolve it). Instead of assigning Order directly, try this:

- first, create view mappers for both entities

```php
class OrderView extends AbstractView
{
    public $sum;
    
    public $products;
    
    public function __construct(Order $order)
    {
        $this->sum = $order->getSum();
        $this->products = ProductView::lazyCollection(function () use ($order) {
            return $order->getProducts();
        });
    }
}

class ProductView extends AbstractView
{
    public $name;
    
    public function __construct(Product $product)
    {
        $this->name = $product->getName();
    } 
}
```

- then assign it like this:

```php
/** @Route("/orders/{id}") */
public function showDetailed(Order $order): Response
{
    return $this->render('detailed.html.twig', [
        'order' => new OrderView($order),
    ]);
}
``` 

As you can see, very minor changes.

---

The advantages are that public methods in entities can be safely renamed and their usages found. 

Instead of using ``lazyCollection()``, you _could_ write this:

```php
class OrderView extends AbstractView
{
    public $sum;
    
    public $products;
    
    public function __construct(Order $order)
    {
        $this->sum = $order->getSum();
        $this->products = ProductView::fromIterable($order->getProducts());
    }
}
```

The problem is if your template does not iterate thru products; method ``getProducts()`` would **always** be executed, even if you don't display its results. For lazy loaded entities, it means another query will be executed for no reason.


But by using ``lazyCollection()``, method will be triggered **only** when first accessed in templates and only once. 

### Issues
If you rename properties in your view files, yes, it will break Twig. The difference is that it is centralized in one very small piece of code and it is hard to miss it. 

It is also much less of a problem if those properties are marked as unused by IDE; they are not here for the logic.

If you check the code in AbstractView, you will see constructor being commented. It is due to bug in phpstan that is reported and fixed, but not merged at this moment. As soon as it is done, code will be updated.

### Best practices 
- Use upcoming [arrow functions](https://wiki.php.net/rfc/arrow_functions_v2). Your lazy assignments would be even simpler:
```php
class OrderView extends AbstractView
{
    public $sum;
    
    public $products;
    
    public function __construct(Order $order)
    {
        $this->sum = $order->getSum();
        $this->products = ProductView::lazyCollection(fn() => $order->getProducts());
    }
}
```
- Keep in mind that view classes are reusable; you don't need individual classes per route, but per entity. 
- Always use ``lazyCollection()``. The syntax is clear and you will get autocomplete for child views. 
- If you need one-time calculation of something, you can use anon class instead of new file:
```php
$view = new class ($orders) extends AbstractView
{
    public $total = 0;
    
    public $orders;

    public function __construct(iterable $orders)
    {
        $this->orders = OrderView::fromIterable($orders);
        // this can be one-liner using array_reduce and arrow function
        foreach ($orders as $order) {
            $this->total += $order->getSum();
        }
    }
};
``` 
- If your view class deals with multiple results, typehint it with ``iterable`` instead of ``array``; it makes it easy to switch from fixed array to pagination tools that implements Iterator interface.
