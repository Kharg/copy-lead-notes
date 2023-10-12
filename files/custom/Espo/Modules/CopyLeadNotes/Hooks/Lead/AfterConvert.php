<?php

namespace Espo\Modules\CopyLeadNotes\Hooks\Lead;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Utils\Config;

class AfterConvert extends \Espo\Core\Hooks\Base
{
    protected $config;

    public static $order = 10;

    public function __construct(EntityManager $entityManager, Config $config, FileStorageManager $fileStorageManager)
    {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->fileStorageManager = $fileStorageManager;
    }

    public function afterSave(Entity $entity, array $options = [])
    {
        // Only proceed if the Lead is being converted
        if (!$entity->get('convertedAt')) {
            return;
        }

        // Only proceed if one of the three specific settings is enabled
        if (
            !$this->config->get('copyLeadNotesForAccount') &&
            !$this->config->get('copyLeadNotesForContact') &&
            !$this->config->get('copyLeadNotesForOpportunity')
        ) {
        return;
        }

        $leadId = $entity->id;

        // Fetch related stream notes for this Lead
        $notes = $this->getRelatedNotes($leadId);

        // Duplicate notes for Account, Contact, and Opportunity based on settings
        $this->duplicateNotes($notes, $entity);
    }

    protected function getRelatedNotes($leadId)
    {
        return $this->getEntityManager()->getRepository('Note')->where([
            'parentId' => $leadId,
            'parentType' => 'Lead'
        ])->find();
    }

    protected function duplicateNotes($notes, $leadEntity)
    {
        foreach ($notes as $note) {
            if ($note->get('type') !== 'Post') {
                continue;
            }

            // Duplicate for Account
            if ($leadEntity->get('createdAccountId') && $this->config->get('copyLeadNotesForAccount')) {
                $this->createNoteDuplicate($note, 'Account', $leadEntity->get('createdAccountId'));
            }
            // Duplicate for Contact
            if ($leadEntity->get('createdContactId') && $this->config->get('copyLeadNotesForContact')) {
                $this->createNoteDuplicate($note, 'Contact', $leadEntity->get('createdContactId'));
            }
            // Duplicate for Opportunity
            if ($leadEntity->get('createdOpportunityId') && $this->config->get('copyLeadNotesForOpportunity')) {
                $this->createNoteDuplicate($note, 'Opportunity', $leadEntity->get('createdOpportunityId'));
            }
        }
    }

    protected function createNoteDuplicate($note, $type, $id)
    {
        $noteDuplicate = $this->getEntityManager()->getEntity('Note');
        $noteDuplicate->set([
            'type' => $note->get('type'),
            'post' => $note->get('post'),
            'parentId' => $id,
            'parentType' => $type,
            'createdById' => $note->get('createdById'),
            'createdByName' => $note->get('createdByName'),
            'createdAt' => $note->get('createdAt'),
            'modifiedAt' => $note->get('modifiedAt'),
        ]);
    
        // First, save the note duplicate so it gets an ID.
        $this->getEntityManager()->saveEntity($noteDuplicate, ['skipAll' => true]);

        $attachmentIds = [];
        $attachments = $this->getNoteAttachments($note->id);
        foreach ($attachments as $attachment) {
            $newAttachment = $this->duplicateAttachment($attachment, $noteDuplicate->id); // Now it has an ID
            if ($newAttachment) {
                $attachmentIds[] = $newAttachment->id;
            }
        }

        if (!empty($attachmentIds)) {
            $noteDuplicate->set('attachmentsIds', $attachmentIds);
            $this->getEntityManager()->saveEntity($noteDuplicate, ['skipAll' => true]); // Save the note again to update the attachmentsIds
        }
    }

    protected function getNoteAttachments($noteId)
    {
        return $this->getEntityManager()->getRepository('Attachment')->where([
            'parentId' => $noteId,
            'parentType' => 'Note'
        ])->find();
    }

    protected function duplicateAttachment($attachment, $newNoteId)
    {
        $attachmentRepo = $this->getEntityManager()->getRepository('Attachment');
    
        // Use the built-in getCopiedAttachment method
        $newAttachment = $attachmentRepo->getCopiedAttachment($attachment, 'Attachment');
    
        // After copying, set relatedType and relatedId
        $newAttachment->set([
            'parentType' => 'Note',
            'parentId' => $newNoteId
        ]);
    
        $this->getEntityManager()->saveEntity($newAttachment, ['skipAll' => true]);

        return $newAttachment;
    }
}
