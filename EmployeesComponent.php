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

    public function initialize($onlyActivityLogs = false)
    {
        $this->employeesPackage = $this->usePackage(Employees::class);

        $this->setActivityLogsPackage($this->employeesPackage, 'employees/activitylogs');

        if ($onlyActivityLogs) {
            return;
        }

        $this->companiesPackage = $this->usePackage(Companies::class);

        $this->setNotificationPackage($this->employeesPackage);
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
                foreach ($employeesArr as &$employees) {
                    if (!isset($designations[$employees['designation']])) {
                        $designations[$employees['designation']]['id'] = $employees['designation'];
                        $designations[$employees['designation']]['name'] = ucfirst($employees['designation']);
                    }
                    $employee = $this->employeesPackage->getEmployees($employees['id']);

                    if (isset($employee['contact'])) {
                        $employees['full_name'] = $employee['contact']['full_name'];
                    }
                }
            }

            $this->view->designations = $designations;

            $this->view->employees = $employeesArr;

            if ($this->getData()['id'] != 0) {
                $employee = $this->employeesPackage->getEmployees((int) $this->getData()['id']);

                if (!$employee) {
                    return $this->throwIdNotFound();
                }

                if (isset($employeesArr[$employee['id']])) {//Remove own information.
                    unset($employeesArr[$employee['id']]);
                }

                $this->view->employee = $employee;

                $this->view->account = [];

                $account = $this->basepackages->accounts->checkAccountBy($employee['id'], false, 'profile_package_row_id');

                if ($account) {
                    $this->view->account = [$account];
                }
            }

            $this->view->pick('employees/view');

            return;
        }

        $controlActions =
            [
                'actionsToEnable'       =>
                [
                    'edit'      => 'employees',
                    'remove'    => 'employees/remove'
                ]
            ];

        $replaceColumns =
            function ($dataArr) {
                if ($dataArr && is_array($dataArr) && count($dataArr) > 0) {
                    $organisations = [];
                    $organisationsArr = $this->companiesPackage->getCompaniesByBusinessType();
                    if ($organisationsArr && count($organisationsArr) > 0) {
                        foreach ($organisationsArr as $organisation) {
                            $organisation['name'] = $organisation['name'] . ' (' . $organisation['pan'] . ')';
                            $organisations[$organisation['id']] = $organisation;
                        }
                    }

                    foreach ($dataArr as &$data) {
                        $account = $this->basepackages->accounts->checkAccountBy($data['id'], false, 'profile_package_row_id');

                        if ($account) {
                            $data['account_id'] = $account['email'] . ' (' . $account['id'] . ')';
                        }

                        if (isset($data['designation']) && $data['designation'] !== '') {
                            $data['designation'] = strtoupper($data['designation']);
                        } else {
                            $data['designation'] = '-';
                        }

                        if (isset($organisations[$data['organisation_id']])) {
                            $data['organisation_id'] = $organisations[$data['organisation_id']]['name'];
                        }
                    }
                }

                return $dataArr;
            };

        if ($this->request->isPost()) {
            $this->employeesPackage->setFFRelations(true);
            $this->employeesPackage->setFFRelationsConditions(['addresses' => ['package_name', '=', 'Employees'], 'contact' => ['package_name', '=', 'Employees']]);
        }

        $this->generateDTContent(
            $this->employeesPackage,
            'employees/view',
            [],
            ['employee_id', 'first_name', 'last_name', 'contact_mobile', 'designation', 'organisation_id', 'account_id'],
            true,
            ['employee_id', 'first_name', 'last_name', 'contact_mobile', 'designation', 'organisation_id', 'account_id'],
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

        $this->setNotificationPackage();

        if ($this->employeesPackage->packagesData->responseCode === 0) {
            $this->addToNotification('remove', 'Archived employee ' . $this->employeesPackage->packagesData->last['name'], null, $this->employeesPackage->packagesData->last);
        }
    }
}