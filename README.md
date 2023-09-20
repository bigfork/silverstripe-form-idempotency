# Silverstripe Form Idempotency

> Idempotence is the property of certain operations in mathematics and computer science whereby they can be applied
> multiple times without changing the result beyond the initial application.
[Wikipedia](https://en.wikipedia.org/wiki/Idempotence)

This module is designed to help prevent duplicate form submissions, particularly when users click a submit button
repeatedly in a short period. For standard HTML forms this will result in the browser “cancelling” previous POST
request(s) and submitting new ones, however if the server has already begun processing the request then this comes too
late to prevent the POST submissions being handled multiple times.

Note that this is not a replacement for spam protection, in fact this is designed specifically for forms _without_ spam
protection as many spam protectors will already ensure a form can only be submitted once.

## Overview

- When a form is rendered, a unique “idempotency key” is generated and stored in a hidden field
- On submission, the server will check the user’s session to see if this key has already been submitted
- If the key hasn’t been seen before (i.e. the first submit), the form handler is called as usual
- The result of the form action is then serialized and stored in the session
- If the form is submitted again with the same idempotency key, the form handler is skipped and the serialized result
from the session is returned

## Installation

`composer require bigfork/silverstripe-form-idempotency`

## Usage

Call `$form->enableIdempotency()` when constructing your form.

## Limitations

- The result returned from the form handler (your “`public function doSomething()`”) is stored in the session, so it
must be serializable. A typical response (e.g. a redirect `HTTPResponse`, or an `HTMLText` instance like a rendered
template) is already serializable, but if you’re doing anything heavily custom you may need to check.
- If your form handler redirects, there’s a narrow window between (1) when the client receives the redirect response &
the browser issues a GET request to the destination, and (2) when the client receives the response from the GET request 
to the destination. If the user clicks submit again within this window, session messages (such as form or “flash”
messages) may be lost, as the GET request will cause them to be rendered and cleared. This is rare, and the cached
response described above will still be returned, but if you rely on one-time session messages being displayed as
feedback then it’s possible these may not always appear.
