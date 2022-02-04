<?php

namespace Icinga\Module\Idosnap\Web\Form;

use Exception;
use gipfl\Translation\TranslationHelper;
use gipfl\Web\Form;
use gipfl\ZfDb\Adapter\Pdo\PdoAdapter;
use Icinga\Module\Idosnap\Snapshot;
use Ramsey\Uuid\UuidInterface;

class SnapshotForm extends Form
{
    use TranslationHelper;
    /** @var PdoAdapter */
    protected $idoDb;

    /** @var PdoAdapter */
    protected $db;

    /** @var UuidInterface */
    protected $uuid;

    public function __construct(PdoAdapter $idoDb, PdoAdapter $db)
    {
        $this->idoDb = $idoDb;
        $this->db = $db;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    protected function assemble()
    {
        $this->addElement('text', 'label', [
            'label' => $this->translate('Name'),
        ]);
        $this->addElement('submit', 'submit', [
            'label' => $this->translate('Create')
        ]);
    }

    /**
     * @throws Exception
     */
    protected function onSuccess()
    {
        $this->uuid = Snapshot::create($this->getValue('label'), $this->idoDb, $this->db);
    }
}
