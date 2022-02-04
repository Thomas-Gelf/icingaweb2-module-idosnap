<?php

namespace Icinga\Module\Idosnap\Web\Form;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form\Feature\NextConfirmCancel;
use gipfl\Web\InlineForm;
use gipfl\ZfDb\Adapter\Pdo\PdoAdapter;
use Icinga\Module\Idosnap\Db\Schema;
use Ramsey\Uuid\UuidInterface;

class DeleteForm extends InlineForm
{
    use TranslationHelper;

    /** @var PdoAdapter */
    protected $db;
    /** @var UuidInterface */
    protected $uuid;

    public function __construct(PdoAdapter $db, UuidInterface $uuid)
    {
        $this->db = $db;
        $this->uuid = $uuid;
    }

    protected function assemble()
    {
        $this->add(Icon::create('cancel'));
        $confirm = new NextConfirmCancel(
            NextConfirmCancel::buttonNext($this->translate('Delete')),
            NextConfirmCancel::buttonConfirm($this->translate('YES, really delete')),
            NextConfirmCancel::buttonCancel($this->translate('Cancel'), [
                'formnovalidate' => true
            ])
        );
        $confirm->addToForm($this);
    }

    protected function onSuccess()
    {
        $this->db->delete(Schema::TABLE_SNAPSHOTS, $this->db->quoteInto('uuid = ?', $this->uuid->getBytes()));
    }
}
