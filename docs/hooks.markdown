# Hooks [hooks] #

A Slim application provides a set of hooks to which you can register your own callbacks.

A "hook" is a moment in the Slim application lifecycle at which a priority list of callables assigned to the hook will be invoked. A hook is identified by a string name.

A "callable" is anything that returns `true` for `is_callable()`. A callable is assigned to a hook and is invoked when the hook is called.