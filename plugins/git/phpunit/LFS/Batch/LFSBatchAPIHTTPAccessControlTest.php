<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Git\LFS\Batch;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Git\HTTP\HTTPAccessControl;
use Tuleap\Git\LFS\Batch\Request\BatchRequest;
use Tuleap\Git\Permissions\AccessControlVerifier;

class LFSBatchAPIHTTPAccessControlTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $http_access_control;
    private $user_manager;
    private $access_control_verifier;

    protected function setUp()
    {
        $this->http_access_control     = \Mockery::mock(HTTPAccessControl::class);
        $this->user_manager            = \Mockery::mock(\UserManager::class);
        $this->access_control_verifier = \Mockery::mock(AccessControlVerifier::class);
    }

    public function testAnonymousUserCanDownloadFromAPublicRepository()
    {
        $this->http_access_control->shouldReceive('getUser')->andReturns(null);

        $batch_api_access_control = new LFSBatchAPIHTTPAccessControl(
            $this->http_access_control,
            $this->user_manager,
            $this->access_control_verifier
        );

        $url_verification = \Mockery::mock(\URLVerification::class);
        $repository       = \Mockery::mock(\GitRepository::class);
        $batch_request    = \Mockery::mock(BatchRequest::class);
        $batch_request->shouldReceive('isRead')->andReturns(true);

        $this->assertTrue($batch_api_access_control->canAccess($url_verification, $repository, $batch_request));
    }

    public function testAuthenticatedUserWithReadPermissionCanDownload()
    {
        $pfo_user = \Mockery::mock(\PFO_User::class);
        $pfo_user->shouldReceive('getUnixName')->andReturns('username');
        $this->http_access_control->shouldReceive('getUser')->andReturns($pfo_user);

        $user = \Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($user);

        $batch_api_access_control = new LFSBatchAPIHTTPAccessControl(
            $this->http_access_control,
            $this->user_manager,
            $this->access_control_verifier
        );

        $url_verification = \Mockery::mock(\URLVerification::class);
        $repository       = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('userCanRead')->andReturns(true);
        $batch_request = \Mockery::mock(BatchRequest::class);
        $batch_request->shouldReceive('isRead')->andReturns(true);
        $batch_request->shouldReceive('isWrite')->andReturns(false);

        $this->assertTrue($batch_api_access_control->canAccess($url_verification, $repository, $batch_request));
    }

    public function testAuthenticatedUserWithWritePermissionCanUpload()
    {
        $pfo_user = \Mockery::mock(\PFO_User::class);
        $pfo_user->shouldReceive('getUnixName')->andReturns('username');
        $this->http_access_control->shouldReceive('getUser')->andReturns($pfo_user);

        $user = \Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($user);

        $this->access_control_verifier->shouldReceive('canWrite')->andReturns(true);

        $batch_api_access_control = new LFSBatchAPIHTTPAccessControl(
            $this->http_access_control,
            $this->user_manager,
            $this->access_control_verifier
        );

        $url_verification = \Mockery::mock(\URLVerification::class);
        $repository       = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('userCanRead')->andReturns(true);
        $batch_request = \Mockery::mock(BatchRequest::class);
        $batch_request->shouldReceive('isRead')->andReturns(false);
        $batch_request->shouldReceive('isWrite')->andReturns(true);
        $batch_request->shouldReceive('getReference')->andReturns(null);

        $this->assertTrue($batch_api_access_control->canAccess($url_verification, $repository, $batch_request));
    }

    /**
     * @expectedException \Tuleap\Request\NotFoundException
     */
    public function testUserWithoutReadPermissionDoesNotSeeTheRepository()
    {
        $pfo_user = \Mockery::mock(\PFO_User::class);
        $pfo_user->shouldReceive('getUnixName')->andReturns('username');
        $this->http_access_control->shouldReceive('getUser')->andReturns($pfo_user);

        $user = \Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($user);

        $batch_api_access_control = new LFSBatchAPIHTTPAccessControl(
            $this->http_access_control,
            $this->user_manager,
            $this->access_control_verifier
        );

        $url_verification = \Mockery::mock(\URLVerification::class);
        $repository       = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('userCanRead')->andReturns(false);
        $batch_request = \Mockery::mock(BatchRequest::class);

        $this->assertTrue($batch_api_access_control->canAccess($url_verification, $repository, $batch_request));
    }
}
