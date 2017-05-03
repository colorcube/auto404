.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual:

Administrator Manual
====================

when a requested page couldn't be found in TYPO3 it call this extension.

This is what happens then

- check if were in a loop and if so show the internal 404 page
- check if page exists on other domain and if so do a 301 redirect
- show custom error page if exists
- redirect to root page on domain if possible
- show internal 404 page and exit


Setup
-----

No configuration is needed but the extension depends on some prerequisite.

- Domain records exists
- that's it



Custom 404 Page
---------------

A custom 404 page is a normal page. I suggest you mark it with 'not in menu'

Two things are a must for that page to work:

- it has to be a child page of the page which has the domain record on it
- put 'http404' in the field *url alias*

To test if your page work:

- preview the page
- call it with ?id=http404 (e.g. http://example.com/?id=http404)
- call a page which doesn't exists (e.g. http://example.com/?id=99999999)

Remarks
-------

It seems weird but the topic is in fact complex. Unavailable configuration, a bit clunky core api, absence of magic.

The extension is not heavily tested in different environments. For me it works as expected, but you should test it with your setup - which is a good idea anyways.


Debugging
---------

In ``auto404/Classes/Hooks/FrontendHook.php::log`` theres a commented line with ``error_log()``. Uncomment the line and you get some logging in the php error log.
