<?php
/**
 * @package framework.persistentdocument.transformer
 */
class f_persistentdocument_transformer_DefaultTransformer implements f_persistentdocument_transformer_Transformer
{
    /**
     * @var f_persistentdocument_PersistentDocumentModel
     */
    private $srcModel;
    
    /**
     * @var f_persistentdocument_PersistentDocumentModel
     */
    private $destModel;
    
    /**
     * @var array<String>
     */
    private $propertiesName;
    
     /**
     * @var array<String>
     */
    private $i18NpropertiesName;
       
    /**
     * @param f_persistentdocument_PersistentDocumentModel $srcModel
     * @param f_persistentdocument_PersistentDocumentModel $destModel
     */
    public function __construct($srcModel, $destModel)
    {
        $this->srcModel = $srcModel;
        $this->destModel = $destModel;
        $this->propertiesName = Transformers::buildCommonPropertiesArray($srcModel, $destModel);
        $this->i18NpropertiesName = Transformers::buildCommonI18NPropertiesArray($srcModel, $destModel);
    }
    
    /**
     * @param f_persistentdocument_PersistentDocument  $sourceDocument
     * @param f_persistentdocument_PersistentDocument $destDocument
     */
    public function transform($sourceDocument, &$destDocument)
    {
       if ($sourceDocument->getDocumentModelName() != $this->srcModel->getName()) 
       {
           throw new IllegalArgumentException('sourceDocument', $this->srcModel->getName());
       }
       
       if ($destDocument->getDocumentModelName() != $this->destModel->getName()) 
       {
           throw new IllegalArgumentException('destDocument', $this->destModel->getName());
       }  
       
       Transformers::copyProperties($sourceDocument, $destDocument, $this->propertiesName, $this->i18NpropertiesName);
    }
}
