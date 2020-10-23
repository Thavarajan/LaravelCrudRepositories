<?php

namespace Thavam\Repositories\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Thavam\Repositories\Helpers\FormatResponseHelper;

abstract class CrudBase
{
    public $unauthorized = Response::HTTP_UNAUTHORIZED;
    public $Forbidden = Response::HTTP_FORBIDDEN;

    /**
     * Used to format the reponse.
     *
     * @var Thavam\Repositories\Helpers\FormatResponseHelper
     */
    public $formatResponse;

    /**
     * Hold the model for the current Repository.
     *
     * @var Eloquent Model
     */
    protected $model;

    /**
     * Hold the History model for the current Repository.
     *
     * @var Eloquent Model
     */
    protected $historyModel;

    /**
     * Denoted that the current repo model has created by field.
     */
    public $createdBy = true;

    /**
     * Used to update the Model data
     * before save
     * Note:
     * it must return model data.
     *
     * @return data that will be saved
     */
    public function beforeSave($data, $isEditMode = false)
    {
        // can be override in the derived class
        return $data;
    }

    /**
     * Used to the Model data
     * if needed it can be  override in the child class.
     *
     * @return Model current saved model data
     */
    public function afterSave($model, $isEditMode = false)
    {
        // can be override in the derived class
    }

    /**
     * Used to update the Model data
     * before save
     * Note:
     * it must return data.
     *
     * @return data that will be saved
     */
    public function beforeDelete($data)
    {
        // can be override in the derived class
        return $data;
    }

    /**
     * Used to the Model data
     * if needed it can be  override in the child class.
     *
     * @return Model current saved model data
     */
    public function afterDelete($model)
    {
        // can be override in the derived class
    }

    /**
     * Inject the Model During runtime to use the common functions
     * Usually this function is not executed here.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->formatResponse = new FormatResponseHelper();
    }

    public function insertModel($data)
    {
        $newmodel = null;
        try {
            DB::beginTransaction();
            if (Auth::check() && $this->createdBy) {
                $data['created_by'] = Auth::user()->id;
            }
            $newmodel = new $this->model();
            $newmodel->fill($data);
            $newmodel = $this->beforeSave($newmodel);
            $newmodel->save();
            $newmodel->refresh();
            $data = $this->afterSave($newmodel);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->responseError($e);
        }

        return  $this->transResponse($newmodel, Response::HTTP_CREATED, 'Record successfully created');
    }

    public function updateModel($data, $id)
    {
        $umodel = null;
        try {
            DB::beginTransaction();
            $umodel = $this->model->findOrFail($id);
            $this->InsertHistory('UPDATE', $umodel);
            $umodel->fill($data);
            $umodel = $this->beforeSave($umodel, true);
            $umodel->save();
            $umodel->refresh();
            $this->afterSave($umodel, true);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->responseError($e);
        }

        return $this->transResponse($umodel, Response::HTTP_ACCEPTED, 'Record successfully updated');
    }

    public function deleteModel(int $id)
    {
        try {
            DB::beginTransaction();
            $record = $this->model->find($id);
            if ($record) {
                $this->InsertHistory('DELETE', $record);
                $this->beforeDelete($record);
                $record->delete();
                $this->afterDelete($record);
                DB::commit();
            } else {
                throw new ModelNotFoundException();
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->responseError($e);
        }

        return $this->responseMessage(trans('Record successfully deleted'), Response::HTTP_ACCEPTED);
    }

    /**
     * Used to get overridden in child classes
     * if its need to add some more dat in the end result.
     *
     * @param mixed        $result
     * @param ResponseCode $code
     *
     * @return json String Response
     */
    public function responseJSON($result, $code = Response::HTTP_OK)
    {
        return $this->formatResponse->responseJSON($result, $code);
    }

    /**
     * Used to get overridden in child classes
     * if its need to add some more dat in the end result.
     *
     * @param mixed        $result
     * @param ResponseCode $code
     *
     * @return json String Response
     */
    public function transResponse($result, $code = Response::HTTP_OK, $message = '')
    {
        return $this->responseJSON($result, $code);
    }

    /**
     * Used to send ther message as json Response.
     *
     * @param string       $message to send as a json
     * @param ResponseCode $code    Any http response code
     *
     * @return Json string response
     */
    public function responseMessage($message, $code = Response::HTTP_OK)
    {
        return $this->responseJSON(['message' => $message], $code);
    }

    /**
     * send an exception as json reponse with some default errorcode.
     *
     * @param \Exception $exception Genereal exception that can be sent to the user
     *
     * @return Json string response with some invalid http reponsecode
     */
    public function responseError(\Exception $exception)
    {
        return $this->formatResponse->responseError($exception);
    }

    /**
     * Used to insert the History reocrd in the history table.
     *
     * @param string $action
     */
    public function insertHistory($action, $record)
    {
        if ($this->historyModel) {
            $data = $record->toArray();
            $data['action'] = $action;
            $data['entry_id'] = $data['id'];
            unset($data['id']);
            $this->historyModel->create($data);
        }
    }
}
