<?php

/**
 * index view for products
 *
 * Filename:        index.view.php
 * Location:        App/views/products/
 * Project:         sk-saas-vanilla-mvc
 * Date Created:    05/09/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */


/* Load HTML header and navigation areas */
loadPartial("header");
loadPartial('navigation');

?>

<main class="container mx-auto bg-zinc-50 py-8 px-4 shadow shadow-black/25 rounded-b-lg flex flex-col flex-grow">
  <article>
    <header class="bg-zinc-700 text-zinc-200 -mx-4 -mt-8 p-8 mb-8 flex">
      <h1 class="grow text-2xl font-bold ">Products</h1>
      <p class="text-md flex-0 px-8 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded transition ease-in-out duration-500">
        <a href="/products/create">Add Product</a>
      </p>
    </header>

    <section class="text-xl text-zinc-500 my-8">
      <?php if (isset($keywords)) : ?>
        <p>Search Results for: <?= htmlspecialchars($keywords) ?></p>
        <p><?= count($products ?? []) ?> product(s) found</p>
      <?php else : ?>
        <p>All Products</p>
      <?php endif; ?>

      <?= loadPartial('message') ?>
    </section>

    <section class="grid grid-cols-3 gap-8 ">
      <?php
      foreach ($products ?? [] as $product):
      ?>
        <!--            article>(header>h4{Name})+(section>p{Description})+(footer>p{Price})-->
        <article class="max-w-96 min-w-64 bg-white shadow rounded flex flex-col">
          <header class="bg-zinc-700 text-zinc-200 text-lg p-4 -mt-2 mb-4 rounded-t flex-0">
            <h4>
              <?= $product->name ?>
            </h4>
          </header>
          <section class="flex-grow grid grid-cols-5 px-4 py-0 gap-4">
            <p class="col-span-2 p-0 pt-2">
              <img class="w-24 h-24 " src="https://dummyimage.com/200x200/a1a1aa/fff&text=Image+Here"
                alt="">
            </p>
            <p class="col-span-3 text-zinc-600 p-0"><?= $product->description ?></p>
          </section>
          <a href="/products/<?= $product->id ?>"
            class="w-full text-center text-sm text-zinc-900 font-medium
                  bg-zinc-200 hover:bg-zinc-300 block
                  py-2 mt-4 -mb-2 rounded-b
                  transition ease-in-out duration-500">
            <div class="flex flex-start ml-4">Price: <?= $product->price / 100 ?></div>
            <div class="block item-center font-bold ">
              Details...
            </div>
          </a>
        </article>
      <?php
      endforeach
      ?>
    </section>

  </article>
</main>


<?php
loadPartial("footer");
