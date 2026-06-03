<?php

namespace Apps\Tms\Components\Employees;

use Apps\Tms\Packages\Adminltetags\Traits\DynamicTable;
use Apps\Tms\Packages\Companies\Companies;
use Apps\Tms\Packages\Employees\Employees;
use System\Base\BaseComponent;

class EmployeesComponent extends BaseComponent
{
    use DynamicTable;

    protected $employeesPackage;

    protected $companiesPackage;

    public function initialize()
    {
        $this->employeesPackage = $this->usePackage(Employees::class);

        $this->companiesPackage = $this->usePackage(Companies::class);
    }

    /**
     * @acl(name=view)
     */
    public function viewAction()
    {
        if (isset($this->getData()['id'])) {
            $organisations = $this->companiesPackage->getCompaniesByBusinessType();
            if ($organisations && count($organisations) > 0) {
                foreach ($organisations as &$organisation) {
                    $organisation['name'] = $organisation['name'] . ' (' . $organisation['pan'] . ')';
                }
            }
            $this->view->organisations = $organisations;

            $designations = [];

            $employeesArr = $this->employeesPackage->getAll()->employees;
            if ($employeesArr && count($employeesArr) > 0) {
                foreach ($employeesArr as $employees) {
                    if (!isset($designations[$employees['designation']])) {
                        $designations[$employees['designation']]['id'] = $module['designation'];
                        $designations[$employees['designation']]['name'] = ucfirst($module['designation']);
                    }
                }
            }

            $this->view->designations = $designations;

            $this->view->employees = $employeesArr;

            if ($this->getData()['id'] != 0) {
                $employee = $this->employeesPackage->getVehicle((int) $this->getData()['id']);

                if (!$employee) {
                    return $this->throwIdNotFound();
                }

                if (isset($employeesArr[$employee['id']])) {//Remove own information.
                    unset($employeesArr[$employee['id']]);
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
            [],
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
     * @notification(name=add)
     */
    public function addAction()
    {
        $this->requestIsPost();

        $this->employeesPackage->addEmployee($this->postData());

        $this->addResponse(
            $this->employeesPackage->packagesData->responseMessage,
            $this->employeesPackage->packagesData->responseCode
        );

        if ($this->employeesPackage->packagesData->responseCode === 0) {
            $this->addToNotification('add', 'Added new employee ' . $this->employeesPackage->packagesData->last['name'], null, $this->employeesPackage->packagesData->last);
        }
    }

    /**
     * @acl(name=update)
     * @notification(name=update)
     */
    public function updateAction()
    {
        $this->requestIsPost();

        $this->employeesPackage->useMutex(true);

        $this->employeesPackage->updateEmployee($this->postData());

        $this->addResponse(
            $this->employeesPackage->packagesData->responseMessage,
            $this->employeesPackage->packagesData->responseCode
        );

        if ($this->employeesPackage->packagesData->responseCode === 0) {
            $this->addToNotification('update', 'Updated employee ' . $this->employeesPackage->packagesData->last['name'], null, $this->employeesPackage->packagesData->last);
        }
    }

    /**
     * @acl(name=remove)
     * @notification(name=remove)
     */
    public function removeAction()
    {
        $this->requestIsPost();

        $this->employeesPackage->removeEmployee($this->postData());

        $this->addResponse(
            $this->employeesPackage->packagesData->responseMessage,
            $this->employeesPackage->packagesData->responseCode
        );

        if ($this->employeesPackage->packagesData->responseCode === 0) {
            $this->addToNotification('remove', 'Archived employee ' . $this->employeesPackage->packagesData->last['name'], null, $this->employeesPackage->packagesData->last);
        }
    }
}