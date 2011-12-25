# Custom Hooks [hooks-custom] #

You may also create and apply your own custom hooks, too. You may then register callables for that hook just as you do with the Slim application's [default hooks](#hooks-default).

    $app = new Slim();
    $app->applyHook('my.hook.name');

When you run the above code, any callables previously assigned to the hook **my.hook.name** will be invoked in order of priority (ascending). As demonstrated in this example, you may register callables to a hook before the hook is created. Think of it this way: when you invoke the Slim application's `applyHook()` instance method, you are asking Slim to invoke all callables currently registered for that hook name.