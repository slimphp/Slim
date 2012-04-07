# Default Hooks [hooks-default] #

These are the default hooks always invoked in a Slim application.

slim.before
:   This hook is invoked before the Slim application is run and before output buffering is turned on. This hook is invoked once during the Slim application lifecycle.

slim.before.router
:   This hook is invoked after output buffering is turned on and before the router is dispatched. This hook is invoked once during the Slim application lifecycle.

slim.before.dispatch
:   This hook is invoked before the current matching route is dispatched. Usually this hook is invoked only once during the Slim application lifecycle; however, this hook may be invoked multiple times if a matching route chooses to pass to a subsequent matching route.

slim.after.dispatch
:   This hook is invoked after the current matching route is dispatched. Usually this hook is invoked only once during the Slim application lifecycle; however, this hook may be invoked multiple times if a matching route chooses to pass to a subsequent matching route.

slim.after.router
:   This hook is invoked after the router is dispatched, before the Response is sent to the client, and after output buffering is turned off. This hook is invoked once during the Slim application lifecycle.

slim.after
:   This hook is invoked after output buffering is turned off and after the Response is sent to the client. This hook is invoked once during the Slim application lifecycle.