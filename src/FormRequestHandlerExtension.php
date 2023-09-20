<?php

namespace Bigfork\SilverstripeFormIdempotency;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\Form;

class FormRequestHandlerExtension extends Extension
{
    public function beforeCallFormHandler(
        HTTPRequest $request,
        string $funcName,
        array $vars,
        Form $form,
        RequestHandler &$subject
    ): void
    {
        // Not enabled, bail out
        if (!$form->getIdempotencyEnabled()) {
            return;
        }

        // Key missing - show generic "technical problem" message to tell the user to refresh and re-submit
        $fieldName = Config::inst()->get(FormExtension::class, 'field_name');
        $key = $vars[$fieldName] ?? null;
        if (!$key) {
            $form->setSessionData($form->getData())
                ->sessionError(_t(
                    "SilverStripe\\Forms\\Form.CSRF_FAILED_MESSAGE",
                    <<<'EOF'
There seems to have been a technical problem. Please click the back button, refresh your browser, and try again.
EOF
                ));
            return;
        }

        $session = $form->getController()->getRequest()->getSession();
        $sessionName = Config::inst()->get(FormExtension::class, 'session_name');
        $keys = $session->get($sessionName) ?? [];
        // Key not encountered yet, so continue as normal
        if (!isset($keys[$key])) {
            return;
        }

        // Idempotency key has already been encountered, so we want to stop the form handler being called. The
        // extension hooks don't allow skipping the form handler, so we have to re-assign $subject to a class that
        // will do nothing when the form handler method is called upon it
        $subject = new class extends RequestHandler {
            public function __call($method, $arguments)
            {
            }
        };
    }

    public function afterCallFormHandler(
        HTTPRequest $request,
        string $funcName,
        array $vars,
        Form $form,
        RequestHandler $subject,
        mixed &$result
    ): void
    {
        // Not enabled, bail out
        if (!$form->getIdempotencyEnabled()) {
            return;
        }

        $fieldName = Config::inst()->get(FormExtension::class, 'field_name');
        $key = $vars[$fieldName] ?? '';

        $session = $form->getController()->getRequest()->getSession();
        $sessionName = Config::inst()->get(FormExtension::class, 'session_name');
        $keys = $session->get($sessionName) ?? [];

        // We've encountered this idempotency key before. The form handler method will already have been skipped in
        // beforeCallFormHandler(), so we just need to return the saved response for this key
        if (isset($keys[$key])) {
            $result = $keys[$key];
            return;
        }

        // Store the result in the session in case the request is replayed
        $keys[$key] = $result;
        $session->set($sessionName, $keys);
    }
}
