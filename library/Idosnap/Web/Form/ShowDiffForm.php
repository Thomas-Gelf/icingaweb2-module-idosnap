<?php

namespace Icinga\Module\Idosnap\Web\Form;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form\Feature\NextConfirmCancel;
use gipfl\Web\InlineForm;
use gipfl\ZfDb\Adapter\Pdo\PdoAdapter;
use Icinga\Module\Idosnap\Db\Schema;
use ipl\Html\FormElement\SelectElement;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ShowDiffForm extends InlineForm
{
    use TranslationHelper;

    /** @var PdoAdapter */
    protected $db;
    /** @var UuidInterface */
    protected $myUuid;
    /** @var UuidInterface */
    protected $uuid;

    public function __construct(PdoAdapter $db, UuidInterface $myUuid)
    {
        $this->db = $db;
        $this->myUuid = $myUuid;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    protected function assemble()
    {
        $this->add(Icon::create('flapping'));
        $confirm = new NextConfirmCancel(
            NextConfirmCancel::buttonNext($this->translate('Diff')),
            NextConfirmCancel::buttonConfirm($this->translate('Show')),
            NextConfirmCancel::buttonCancel($this->translate('Cancel'), [
                'formnovalidate' => true
            ])
        );
        $confirm->showWithConfirm($this->enumSnapshots());
        $confirm->addToForm($this);
    }

    protected function enumSnapshots(): SelectElement
    {
        $select = new SelectElement('uuid', [
            'options'  => [
                null => $this->translate('- please choose -')
            ] + $this->listOtherSnapshots(),
            'required' => true,
        ]);
        $select->getOption($this->myUuid->toString())->setAttribute('disabled', true);

        return $select;
    }

    protected function listOtherSnapshots(): array
    {
        $result = [];
        foreach ($this->db->fetchPairs($this->db->select()->from(Schema::TABLE_SNAPSHOTS, [
            'uuid',
            'label'
        ])->order('ts_created DESC')) as $uuid => $label) {
            $result[Uuid::fromBytes($uuid)->toString()] = $label;
        }

        return $result;
    }

    protected function onSuccess()
    {
        $this->uuid = Uuid::fromString($this->getValue('uuid'));
    }
}
