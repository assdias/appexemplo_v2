<?php

class ProdutoForm extends TPage
{
    protected $form;      // form
    protected $datagrid;  // datagrid
    protected $loaded;
    protected $pageNavigation;  // pagination component
    
    // trait with onSave, onEdit, onDelete, onReload, onSearch...
    use Adianti\Base\AdiantiStandardFormListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('form_exemplo'); // define the database
        $this->setActiveRecord('Produto'); // define the Active Record
        $this->setDefaultOrder('idproduto', 'asc'); // define the default order
        
        // create the form
        $formDin = new TFormDin('Produto');
        $this->form = $formDin->getAdiantiObj();

        $metaTipoController = new MetaTipoController();
        $listMetaTipo = $metaTipoController->getCombo();

        // create the form fields
        $id     = new TEntry('idtipo');
        
        $descricaoLabel = 'Nome';
        $formDinTextField = new TFormDinTextField('descricao',$descricaoLabel,3,true,null,'xxxxi');
        $descricao = $formDinTextField->getAdiantiObj();
        
        $formDinSelectField = new TFormDinSelectField('idmeta_tipo','Meta Tipo', true, $listMetaTipo);
        $idmeta_tipo = $formDinSelectField->getAdiantiObj();
        
        $sit_ativosLabel = 'Ativo';
        $formDinSwitch = new TFormDinSwitch('sit_ativo',$sit_ativosLabel,true);
        $sit_ativos = $formDinSwitch->getAdiantiObj();
        
        // add the form fields
        $this->form->addFields( [new TLabel('Cod', 'red')],    [$id] );
        $this->form->addFields( [new TLabel($descricaoLabel, 'red')],  [$descricao] );
        $this->form->addFields( [new TLabel('Meta Tipo', 'red')],  [$idmeta_tipo], [new TLabel($sit_ativosLabel, 'red')],  [$sit_ativos] );
 
        
        $id->addValidation('Cod', new TRequiredValidator);

        
        // define the form actions
        $this->form->addAction( 'Save', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink( 'Clear',new TAction([$this, 'onClear']), 'fa:eraser red');

        // make id not editable
        //$id->setEditable(FALSE);
        
        // create the datagrid
        $formDinGrid = new TFormDinGrid($this,__CLASS__,'Lista de Produtos','idproduto');
        $formDinGrid->addColumn('idproduto', 'Cod');
        $formDinGrid->addColumn('nom_produto', 'Nome');
        $formDinGrid->addColumn('marca->nom_marca', 'Marca');
        $formDinGrid->addColumn('modelo', 'Modelo');
        $formDinGrid->addColumn('versao', 'Versão');
        $formDinGrid->addColumn('marca->pessoa->nome', 'Empresa');
        $formDinGrid->addColumn('tipo->descricao', 'Tipo');
        $this->datagrid = $formDinGrid->getAdiantiObj();
    
        
        // define row actions
        $action1 = new TDataGridAction([$this, 'onEdit'],   ['key' => '{idproduto}'] );
        $action2 = new TDataGridAction([$this, 'onDelete'], ['key' => '{idproduto}'] );
        
        $this->datagrid->addAction($action1, 'Edit',   'far:edit blue');
        $this->datagrid->addAction($action2, 'Delete', 'far:trash-alt red');
        
        // create the datagrid model
        $this->datagrid->createModel();

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload'))); 

        $panel = new TPanelGroup('Lista de Produtos');
        $panel->add( $this->datagrid );
        $panel->addFooter($this->pageNavigation);

        $panel->addHeaderActionLink( 'Save as PDF', new TAction([$this, 'exportAsPDF'], ['register_state' => 'false']), 'far:file-pdf red' );
        $panel->addHeaderActionLink( 'Save as CSV', new TAction([$this, 'exportAsCSV'], ['register_state' => 'false']), 'fa:table blue' );

        // wrap the page content using vertical box
        $formDinSwitch = new TFormDinBreadCrumb(__CLASS__);
        $vbox = $formDinSwitch->getAdiantiObj();
        $vbox->add($this->form);
        $vbox->add($panel);
        
        parent::add($vbox);
    }

    /**
     * Export datagrid as PDF
     */
    public function exportAsPDF($param)
    {
        try
        {
            // string with HTML contents
            $html = clone $this->datagrid;
            $contents = file_get_contents('app/resources/styles-print.html') . $html->getContents();
            
            // converts the HTML template into PDF
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($contents);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $file = 'app/output/datagrid-export.pdf';
            
            // write and open file
            file_put_contents($file, $dompdf->output());
            
            $window = TWindow::create('Export', 0.8, 0.8);
            $object = new TElement('object');
            $object->data  = $file;
            $object->type  = 'application/pdf';
            $object->style = "width: 100%; height:calc(100% - 10px)";
            $window->add($object);
            $window->show();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Export datagrid as CSV
     */
    public function exportAsCSV($param)
    {
        try
        {
            // get datagrid raw data
            $data = $this->datagrid->getOutputData();
            
            if ($data)
            {
                $file    = 'app/output/datagrid-export.csv';
                $handler = fopen($file, 'w');
                foreach ($data as $row)
                {
                    fputcsv($handler, $row);
                }
                
                fclose($handler);
                parent::openFile($file);
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

}