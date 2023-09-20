<?php

namespace Bigfork\SilverstripeFormIdempotency;

use Exception;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\HiddenField;

class FormExtension extends Extension
{
    private static string $field_name = 'IdempotencyKey';

    private static string $session_name = 'FormIdempotencyKeys';

    protected bool $idempotencyEnabled = false;

    public function getIdempotencyEnabled(): bool
    {
        return $this->idempotencyEnabled;
    }

    /**
     * @throws Exception
     */
    public function enableIdempotency(): Form
    {
        /** @var Form $form */
        $form = $this->getOwner();
        $key = bin2hex(random_bytes(16));

        $fieldName = Config::inst()->get(static::class, 'field_name');
        $field = HiddenField::create($fieldName)
            ->setValue($key)
            ->setForm($form);
        $form->Fields()->push($field);

        $this->idempotencyEnabled = true;
        return $form;
    }
}
