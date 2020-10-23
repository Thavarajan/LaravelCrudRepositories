<?php

namespace Thavam\Repositories\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class RepositoryBase extends CrudBase
{
    /**
     * Used to hold the format response serivice.
     */
    public $formatResponse;

    /**
     * Used to validate any additional requirements
     * apart from the basic rule validations,
     * if the validation is success the rest of the function is executed.
     */
    abstract public function validate(Request $request);

    /**
     * Used to validate any additional requirements during insert
     * apart from the basic rule validations,
     * if the validation is success the rest of the function is executed.
     */
    abstract public function validateInsert(Request $request);

    /**
     * Used to validate any additional requirements during update
     * apart from the basic rule validations,
     * if the validation is success the rest of the function is executed.
     */
    abstract public function validateUpdate(Request $request, $id);

    /**
     * Used to eager load the data
     * if needed it can be  override in the child class.
     *
     * @return array of eagerload data
     */
    public function eagerLoad()
    {
        return [];
    }

    /**
     * Get the Authenticated User.
     */
    public function user()
    {
        return Auth::user();
    }

    /**
     * USed to create a new model.
     *
     * @param Request $request To create a request
     *
     * @return \Illuminate\Http\JsonResponse With newly created model
     */
    public function create(Request $request)
    {
        // create
        $this->validate($request);
        $this->validateInsert($request);
        $data = $request->only($this->model->getFillable());

        return $this->insertModel($data);
    }

    /**
     * Used to update a model based on the request data.
     *
     * @param int $id /Primary key id of the model
     *
     * @return \Illuminate\Http\JsonResponse With updated model
     */
    public function update(Request $request, $id)
    {
        $this->validate($request);
        $this->validateUpdate($request, $id);
        $data = $request->all();

        return $this->updateModel($data, $id);
    }

    /**
     * USed to delete a model.
     *
     * @param int $id /Primary key of the model
     *
     * @return Success message of the delete
     */
    public function delete(int $id)
    {
        return $this->deleteModel($id);
    }

    /**
     * Used to get the Model Query.
     *
     * @return QueryBuilder
     */
    public function getModelQuery($eagerloads = [])
    {
        $query = null;
        $eload = $this->eagerLoad();
        if (count($eagerloads) > 0) {
            $eload = array_merge($eload, $eagerloads);
        }
        if (count($eload) > 0) {
            $query = $this->model->with($eload);
        } else {
            $query = $this->model->query();
        }

        return $query;
    }

    /**
     * Return all the records.
     *
     * @return Model collection
     */
    public function all()
    {
        return $this->getModelQuery()->get();
    }

    /**
     * Used to get the Model details based on the primary key id.
     *
     * @param int $id /primary key id
     *
     * @return Model
     */
    public function getById(int $id, $eagerloads = [])
    {
        $data = $this->getModelQuery($eagerloads)->find($id);
        if ($data) {
            return $data;
        } else {
            return $this->responseError(new ModelNotFoundException());
        }
    }

    /**
     * Used to return the collection of the model.
     *
     * Request
     */
    public function getByCriteria(Request $request)
    {
        $request->validate([
            '*.fieldname' => 'required|string',
            '*.operator' => 'required|string|max:10',
            '*.criteria' => 'required',
        ]);
        $filters = $request->all();
        $query = $this->getModelQuery();
        foreach ($filters as $filter) {
            // code...
            $query = $query->where($filter['fieldname'], $filter['operator'], $filter['criteria']);
        }
        try {
            return $query->get();
        } catch (\Exception $e) {
            return $this->responseError($e);
        }
    }
}
