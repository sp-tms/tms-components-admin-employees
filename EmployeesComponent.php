<?php

namespace Apps\Tms\Components\Employees;

use Apps\Tms\Packages\Adminltetags\Traits\DynamicTable;
use Apps\Tms\Packages\Employees\Employees;
use System\Base\BaseComponent;

class EmployeesComponent extends BaseComponent
{
    use DynamicTable;

    protected $employeesPackage;

    public function initialize()
    {
        $this->employeesPackage = $this->usePackage(Employees::class);
    }

    /**
     * @acl(name=view)
     */
    public function viewAction()
    {
        if (isset($this->getData()['id'])) {
            if ($this->getData()['id'] != 0) {
                $employee = $this->employeesPackage->getVehicle((int) $this->getData()['id']);

                if (!$employee) {
                    return $this->throwIdNotFound();
                }

                $this->view->employee = $employee;
            }

            $this->view->pick('employees/view');

            return;
        }

        $controlActions =
            [
                'actionsToEnable'       =>
                [
                    'edit'      => 'employees'
                ]
            ];

        $conditions = [];
        $conditions['order'] = 'name asc';

        $replaceColumns =
            function ($dataArr) {
                if ($dataArr && is_array($dataArr) && count($dataArr) > 0) {
                    //
                }

                return $dataArr;
            };

        $this->generateDTContent(
            $this->employeesPackage,
            'employees/view',
            $conditions,
            ['employee_id', 'organisation_id'],
            true,
            ['employee_id', 'organisation_id'],
            $controlActions,
            null,
            $replaceColumns,
            'employee_id'
        );

        $this->view->pick('employees/list');
    }

    /**
     * @acl(name=add)
     */
    public function addAction()
    {
        $this->requestIsPost();

        $this->employeesPackage->addEmployee($this->postData());

        $this->addResponse(
            $this->employeesPackage->packagesData->responseMessage,
            $this->employeesPackage->packagesData->responseCode
        );
    }

    /**
     * @acl(name=update)
     */
    public function updateAction()
    {
        $this->requestIsPost();

        $this->employeesPackage->updateEmployee($this->postData());

        $this->addResponse(
            $this->employeesPackage->packagesData->responseMessage,
            $this->employeesPackage->packagesData->responseCode
        );
    }

    /**
     * @acl(name=remove)
     */
    public function removeAction()
    {
        $this->requestIsPost();

        $this->employeesPackage->removeEmployee($this->postData());

        $this->addResponse(
            $this->employeesPackage->packagesData->responseMessage,
            $this->employeesPackage->packagesData->responseCode
        );
    }
}