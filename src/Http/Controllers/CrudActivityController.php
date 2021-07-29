<?php

namespace IlBronza\ActivityLoggerUI\Http\Controllers;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Traits\CRUDBelongsToManyTrait;
use IlBronza\CRUD\Traits\CRUDCreateStoreTrait;
use IlBronza\CRUD\Traits\CRUDDeleteTrait;
use IlBronza\CRUD\Traits\CRUDDestroyTrait;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDNestableTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;
use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;
use IlBronza\CRUD\Traits\CRUDUpdateEditorTrait;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class CrudActivityController extends CRUD
{
    public static $tables = [

        'index' => [
            'fields' => 
            [
                'mySelfPrimary' => 'primary',
            /**
                'mySelfEdit' => 'links.edit',
                'mySelfSee' => 'links.see',
                'mySelfSee' => [
                    'type' => 'links.see',
                    'textParameter' => false
                    'order' => [
                        'priority' => 10,
                        'type' => 'desc'
                    ],
                ],
                'rag_soc' => 'flat',
                'somet_text' => 'editor.text',
                'second_color' => 'editor.color',
                'supplier' => 'relations.belongsTo',
                'color' => 'color',
                'manyToManyModels' => [
                    'type' => 'relations.beongsToMany',
                    'pivot' => 'PivotModelBaseName'
                ],
                'relatedModels' => 'relations.hasMany',
                'belongsToModel' => 'relations.belongsTo',
                'zone' => 'flat',
                'mySelfDelete' => 'links.delete'
            **/
            ]
        ]
    ];

    use CRUDShowTrait;
    use CRUDPlainIndexTrait;
    use CRUDIndexTrait;
    use CRUDEditUpdateTrait;
    use CRUDUpdateEditorTrait;
    use CRUDCreateStoreTrait;

    use CRUDDeleteTrait;
    use CRUDDestroyTrait;

    use CRUDRelationshipTrait;
    use CRUDBelongsToManyTrait;

    use CRUDNestableTrait;

    /**
     * subject model class full path
     **/
    public $modelClass = Activity::class;

    /**
     * http methods allowed. remove non existing methods to get a 403
     **/
    public $allowedMethods = [
        'activityIndex',
        // 'show',
        // 'edit',
        // 'update',
        // 'create',
        // 'store',
        // 'destroy',
        // 'deleted',
        // 'archived',
        // 'reorder',
        // 'stroreReorder'
    ];

    /**
     * to override show view use full view name
     **/
    //public $showView = 'products.showPartial';

    // public $guardedEditDBFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
    // public $guardedCreateDBFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
    // public $guardedShowDBFields = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * relations called to be automatically shown on 'show' method
     **/
    //public $showMethodRelationships = ['posts', 'users', 'operations'];

    /**
        protected $relationshipsControllers = [
        'permissions' => '\IlBronza\AccountManager\Http\Controllers\PermissionController'
    ];
    **/


    /**
     * getter method for 'index' method.
     *
     * is declared here to force the developer to rationally choose which elements to be shown
     *
     * @return Collection
     **/
     /*
        public function getIndexElements()
        {
            return Activity::all();
        }
    */

    /**
     * parameter that decides which fields to use inside index table
     **/
    //  public $indexFieldsGroups = ['index'];

    /**
     * parameter that decides if create button is available
     **/
    //  public $avoidCreateButton = true;



    /**
     * START base methods declared in extended controller to correctly perform dependency injection
     *
     * these methods are compulsorily needed to execute CRUD base functions
     **/
    public function show(Activity $activity)
    {
        //$this->addExtraView('top', 'folder.subFolder.viewName', ['some' => $thing]);

        return $this->_show($activity);
    }

    public function edit(Activity $activity)
    {
        return $this->_edit($activity);
    }

    public function update(Request $request, Activity $activity)
    {
        return $this->_update($request, $activity);
    }

    public function destroy(Activity $activity)
    {
        return $this->_destroy($activity);
    }

    public function activityIndex(string $classBasename, string $primary)
    {
        $classBasename = urldecode($classBasename);

        $this->modelInstance = $classBasename::find($primary);
        mori($this->modelInstance->activities()->get());
    }

    /**
     * END base methods
     **/




     /**
      * START CREATE PARAMETERS AND METHODS
      **/

    // public function beforeRenderCreate()
    // {
    //     $this->modelInstance->agent_id = session('agent')->getKey();
    // }

     /**
      * STOP CREATE PARAMETERS AND METHODS
      **/

}

