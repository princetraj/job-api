#!/usr/bin/env php
<?php

/**
 * Job Portal API Test Script
 * Tests all API endpoints as documented in API_DOCUMENTATION_FRONTEND.md
 */

class APITester
{
    private $baseUrl = 'http://127.0.0.1:8000/api/v1';
    private $tempToken = null;
    private $employeeToken = null;
    private $employerToken = null;
    private $adminToken = null;
    private $testData = [];
    private $results = [];
    private $passedTests = 0;
    private $failedTests = 0;

    public function __construct()
    {
        echo "\n╔════════════════════════════════════════════════════════════╗\n";
        echo "║          Job Portal API Test Suite                        ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";
    }

    private function request($method, $endpoint, $data = null, $token = null, $headers = [])
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($token) {
            $defaultHeaders[] = 'Authorization: Bearer ' . $token;
        }

        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'body' => json_decode($response, true),
            'raw' => $response
        ];
    }

    private function assert($condition, $message)
    {
        if ($condition) {
            $this->passedTests++;
            echo "  ✓ " . $message . "\n";
            return true;
        } else {
            $this->failedTests++;
            echo "  ✗ " . $message . "\n";
            return false;
        }
    }

    private function section($title)
    {
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  " . $title . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }

    private function test($name, $method, $endpoint, $expectedStatus, $data = null, $token = null)
    {
        echo "\n[TEST] $name\n";
        $response = $this->request($method, $endpoint, $data, $token);

        $this->assert(
            $response['status'] === $expectedStatus,
            "Status code is $expectedStatus (got {$response['status']})"
        );

        if ($response['status'] !== $expectedStatus) {
            echo "  Response: " . ($response['raw'] ?: 'Empty') . "\n";
        }

        return $response;
    }

    // ============================================
    // CATALOG API TESTS (PUBLIC)
    // ============================================
    public function testCatalogAPIs()
    {
        $this->section('CATALOG APIs - Public Endpoints');

        // 1. Get Industries
        $response = $this->test(
            '1. GET /catalogs/industries',
            'GET',
            '/catalogs/industries',
            200
        );
        if ($response['status'] === 200 && !empty($response['body']['industries'])) {
            $this->testData['industry_id'] = $response['body']['industries'][0]['id'];
            echo "  → Industry ID: {$this->testData['industry_id']}\n";
        }

        // 2. Get Locations
        $response = $this->test(
            '2. GET /catalogs/locations',
            'GET',
            '/catalogs/locations',
            200
        );
        if ($response['status'] === 200 && !empty($response['body']['locations'])) {
            $this->testData['location_id'] = $response['body']['locations'][0]['id'];
            echo "  → Location ID: {$this->testData['location_id']}\n";
        }

        // 3. Get Categories
        $response = $this->test(
            '3. GET /catalogs/categories',
            'GET',
            '/catalogs/categories',
            200
        );
        if ($response['status'] === 200 && !empty($response['body']['categories'])) {
            $this->testData['category_id'] = $response['body']['categories'][0]['id'];
            echo "  → Category ID: {$this->testData['category_id']}\n";
        }
    }

    // ============================================
    // PLAN API TESTS (PUBLIC)
    // ============================================
    public function testPlanAPIs()
    {
        $this->section('PLAN APIs - Public Endpoints');

        // 1. Get All Plans
        $response = $this->test(
            '1. GET /plans',
            'GET',
            '/plans',
            200
        );

        // Note: Plans might be empty if not seeded, that's OK for testing
        if ($response['status'] === 200 && !empty($response['body']['plans'])) {
            $this->testData['plan_id'] = $response['body']['plans'][0]['id'];
            echo "  → Plan ID: {$this->testData['plan_id']}\n";
        }
    }

    // ============================================
    // AUTHENTICATION API TESTS
    // ============================================
    public function testAuthenticationAPIs()
    {
        $this->section('AUTHENTICATION APIs');

        // 1. Employee Registration - Step 1
        $employeeStep1Data = [
            'email' => 'john.doe.' . time() . '@example.com',
            'mobile' => '+1234567' . rand(100, 999),
            'name' => 'John Doe Test',
            'password' => 'SecurePass123!',
            'gender' => 'M'
        ];

        $response = $this->test(
            '1. POST /auth/register/employee-step1',
            'POST',
            '/auth/register/employee-step1',
            200,
            $employeeStep1Data
        );

        if ($response['status'] === 200 && isset($response['body']['tempToken'])) {
            $this->tempToken = $response['body']['tempToken'];
            $this->testData['employee_email'] = $employeeStep1Data['email'];
            echo "  → Temp Token received\n";

            // 2. Employee Registration - Step 2
            $employeeStep2Data = [
                'dob' => '1990-05-15',
                'address' => [
                    'street' => '123 Main Street',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                    'country' => 'USA'
                ]
            ];

            $response = $this->test(
                '2. POST /auth/register/employee-step2',
                'POST',
                '/auth/register/employee-step2',
                200,
                $employeeStep2Data,
                $this->tempToken
            );

            // 3. Employee Registration - Final Step
            $employeeFinalData = [
                'education' => [
                    [
                        'degree' => 'Bachelor of Science',
                        'university' => 'MIT',
                        'year_start' => '2010',
                        'year_end' => '2014',
                        'field' => 'Computer Science'
                    ]
                ],
                'experience' => [
                    [
                        'company' => 'Tech Corp',
                        'title' => 'Software Engineer',
                        'year_start' => '2014',
                        'year_end' => '2020',
                        'description' => 'Developed web applications'
                    ]
                ],
                'skills' => ['JavaScript', 'React', 'Node.js', 'Python']
            ];

            $response = $this->test(
                '3. POST /auth/register/employee-final',
                'POST',
                '/auth/register/employee-final',
                200,
                $employeeFinalData,
                $this->tempToken
            );

            if ($response['status'] === 200 && isset($response['body']['token'])) {
                $this->employeeToken = $response['body']['token'];
                echo "  → Employee Token received\n";
            }
        }

        // 4. Employer Registration
        $employerData = [
            'company_name' => 'Tech Innovations Inc ' . time(),
            'email' => 'hr.' . time() . '@techinnovations.com',
            'contact' => '+1234567' . rand(100, 999),
            'password' => 'SecurePass123!',
            'address' => [
                'street' => '456 Business Ave',
                'city' => 'San Francisco',
                'state' => 'CA',
                'zip' => '94105',
                'country' => 'USA'
            ],
            'industry_type_id' => $this->testData['industry_id'] ?? null
        ];

        if (!empty($employerData['industry_type_id'])) {
            $response = $this->test(
                '4. POST /auth/register/employer',
                'POST',
                '/auth/register/employer',
                201,
                $employerData
            );

            if ($response['status'] === 201 && isset($response['body']['token'])) {
                $this->employerToken = $response['body']['token'];
                $this->testData['employer_email'] = $employerData['email'];
                echo "  → Employer Token received\n";
            }
        } else {
            echo "  ⚠ Skipping employer registration - no industry_id available\n";
        }

        // 5. Login with Employee
        if (isset($this->testData['employee_email'])) {
            $loginData = [
                'identifier' => $this->testData['employee_email'],
                'password' => 'SecurePass123!'
            ];

            $response = $this->test(
                '5. POST /auth/login (Employee)',
                'POST',
                '/auth/login',
                200,
                $loginData
            );

            $this->assert(
                $response['status'] === 200 && isset($response['body']['token']),
                'Login returns token'
            );
        }

        // 6. Logout
        if ($this->employeeToken) {
            $response = $this->test(
                '6. POST /auth/logout',
                'POST',
                '/auth/logout',
                200,
                [],
                $this->employeeToken
            );
        }
    }

    // ============================================
    // EMPLOYEE API TESTS
    // ============================================
    public function testEmployeeAPIs()
    {
        if (!$this->employeeToken) {
            echo "\n⚠ Skipping Employee API tests - no employee token available\n";
            return;
        }

        $this->section('EMPLOYEE APIs');

        // 1. Get Profile
        $response = $this->test(
            '1. GET /employee/profile',
            'GET',
            '/employee/profile',
            200,
            null,
            $this->employeeToken
        );

        // 2. Update Profile
        $updateData = [
            'field' => 'skills_details',
            'value' => ['PHP', 'Laravel', 'MySQL', 'JavaScript']
        ];

        $response = $this->test(
            '2. PUT /employee/profile/update',
            'PUT',
            '/employee/profile/update',
            200,
            $updateData,
            $this->employeeToken
        );

        // 3. Search Jobs (Public)
        $response = $this->test(
            '3. GET /jobs/search (Public)',
            'GET',
            '/jobs/search?q=developer',
            200
        );

        // 4. Search Jobs (Authenticated)
        $response = $this->test(
            '4. GET /employee/jobs/search',
            'GET',
            '/employee/jobs/search?q=developer',
            200,
            null,
            $this->employeeToken
        );

        // 5. Get Shortlisted Jobs
        $response = $this->test(
            '5. GET /employee/jobs/shortlisted',
            'GET',
            '/employee/jobs/shortlisted',
            200,
            null,
            $this->employeeToken
        );

        // 6. Get Applied Jobs
        $response = $this->test(
            '6. GET /employee/jobs/applied',
            'GET',
            '/employee/jobs/applied',
            200,
            null,
            $this->employeeToken
        );

        // 7. Generate CV
        $response = $this->test(
            '7. GET /employee/cv/generate',
            'GET',
            '/employee/cv/generate',
            200,
            null,
            $this->employeeToken
        );

        // 8. Get CV Requests
        $response = $this->test(
            '8. GET /employee/cv/requests',
            'GET',
            '/employee/cv/requests',
            200,
            null,
            $this->employeeToken
        );

        // 9. Request Professional CV
        $cvRequestData = [
            'notes' => 'I need a modern CV template focused on tech skills',
            'preferred_template' => 'Modern Tech'
        ];

        $response = $this->test(
            '9. POST /employee/cv/request-professional',
            'POST',
            '/employee/cv/request-professional',
            201,
            $cvRequestData,
            $this->employeeToken
        );

        if ($response['status'] === 201 && isset($response['body']['request_id'])) {
            $this->testData['cv_request_id'] = $response['body']['request_id'];

            // 10. Get CV Request Status
            $response = $this->test(
                '10. GET /employee/cv/requests/{requestId}',
                'GET',
                '/employee/cv/requests/' . $this->testData['cv_request_id'],
                200,
                null,
                $this->employeeToken
            );
        }
    }

    // ============================================
    // EMPLOYER API TESTS
    // ============================================
    public function testEmployerAPIs()
    {
        if (!$this->employerToken) {
            echo "\n⚠ Skipping Employer API tests - no employer token available\n";
            return;
        }

        $this->section('EMPLOYER APIs');

        // 1. Get Profile
        $response = $this->test(
            '1. GET /employer/profile',
            'GET',
            '/employer/profile',
            200,
            null,
            $this->employerToken
        );

        // 2. Update Profile
        $updateData = [
            'company_name' => 'Tech Innovations Corp Updated',
            'contact' => '+1987654321'
        ];

        $response = $this->test(
            '2. PUT /employer/profile/update',
            'PUT',
            '/employer/profile/update',
            200,
            $updateData,
            $this->employerToken
        );

        // 3. Create Job
        if (isset($this->testData['location_id']) && isset($this->testData['category_id'])) {
            $jobData = [
                'title' => 'Senior Full Stack Developer',
                'description' => 'We are seeking an experienced full stack developer...',
                'salary' => '$90,000 - $130,000',
                'location_id' => $this->testData['location_id'],
                'category_id' => $this->testData['category_id']
            ];

            $response = $this->test(
                '3. POST /employer/jobs',
                'POST',
                '/employer/jobs',
                201,
                $jobData,
                $this->employerToken
            );

            if ($response['status'] === 201 && isset($response['body']['job_id'])) {
                $this->testData['job_id'] = $response['body']['job_id'];
                echo "  → Job ID: {$this->testData['job_id']}\n";

                // 4. Get Job Details
                $response = $this->test(
                    '4. GET /employer/jobs/{jobId}',
                    'GET',
                    '/employer/jobs/' . $this->testData['job_id'],
                    200,
                    null,
                    $this->employerToken
                );

                // 5. Update Job
                $updateJobData = [
                    'title' => 'Senior Full Stack Developer (Updated)',
                    'description' => 'Updated description...',
                    'salary' => '$95,000 - $135,000',
                    'location_id' => $this->testData['location_id'],
                    'category_id' => $this->testData['category_id']
                ];

                $response = $this->test(
                    '5. PUT /employer/jobs/{jobId}',
                    'PUT',
                    '/employer/jobs/' . $this->testData['job_id'],
                    200,
                    $updateJobData,
                    $this->employerToken
                );

                // 6. Get Job Applications
                $response = $this->test(
                    '6. GET /employer/jobs/{jobId}/applications',
                    'GET',
                    '/employer/jobs/' . $this->testData['job_id'] . '/applications',
                    200,
                    null,
                    $this->employerToken
                );

                // Note: Delete job test at the end
            }
        } else {
            echo "  ⚠ Skipping job creation - location or category not available\n";
        }
    }

    // ============================================
    // PUBLIC API TESTS
    // ============================================
    public function testPublicAPIs()
    {
        $this->section('PUBLIC APIs');

        // 1. Public Job Search
        $response = $this->test(
            '1. GET /jobs/search',
            'GET',
            '/jobs/search?q=developer',
            200
        );

        // 2. Get Public Content List
        $response = $this->test(
            '2. GET /content',
            'GET',
            '/content',
            200
        );

        // 3. Validate Coupon (skip if no plan_id available)
        if (isset($this->testData['plan_id'])) {
            $couponData = [
                'coupon_code' => 'INVALID',
                'plan_id' => $this->testData['plan_id']
            ];

            $response = $this->test(
                '3. POST /coupons/validate (Invalid Coupon)',
                'POST',
                '/coupons/validate',
                200,
                $couponData
            );

            if ($response['status'] === 200) {
                $this->assert(
                    isset($response['body']['valid']) && $response['body']['valid'] === false,
                    'Returns valid: false for invalid coupon'
                );
            }
        } else {
            echo "\n[TEST] 3. POST /coupons/validate (Invalid Coupon)\n";
            echo "  ⏭️ Skipped - no plan_id available (create a plan first)\n";
        }
    }

    // ============================================
    // PAYMENT API TESTS
    // ============================================
    public function testPaymentAPIs()
    {
        if (!$this->employeeToken) {
            echo "\n⚠ Skipping Payment API tests - no employee token available\n";
            return;
        }

        $this->section('PAYMENT APIs');

        // 1. Get Payment History
        $response = $this->test(
            '1. GET /payments/history',
            'GET',
            '/payments/history',
            200,
            null,
            $this->employeeToken
        );

        // Note: Skipping actual subscription test as it requires payment gateway setup
        echo "\n  ⚠ Skipping /payments/subscribe - requires payment gateway configuration\n";
        echo "  ⚠ Skipping /payments/verify - requires payment gateway configuration\n";
    }

    // ============================================
    // RUN ALL TESTS
    // ============================================
    public function runAllTests()
    {
        $startTime = microtime(true);

        // Run tests in order
        $this->testCatalogAPIs();
        $this->testPlanAPIs();
        $this->testAuthenticationAPIs();
        $this->testEmployeeAPIs();
        $this->testEmployerAPIs();
        $this->testPublicAPIs();
        $this->testPaymentAPIs();

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        // Print summary
        $this->section('TEST SUMMARY');
        echo "\n";
        echo "  Total Tests:   " . ($this->passedTests + $this->failedTests) . "\n";
        echo "  ✓ Passed:      " . $this->passedTests . "\n";
        echo "  ✗ Failed:      " . $this->failedTests . "\n";
        echo "  Duration:      " . $duration . "s\n";
        echo "\n";

        if ($this->failedTests === 0) {
            echo "  🎉 All tests passed!\n\n";
        } else {
            echo "  ⚠ Some tests failed. Please review the output above.\n\n";
        }

        return $this->failedTests === 0;
    }
}

// Run the tests
$tester = new APITester();
$success = $tester->runAllTests();
exit($success ? 0 : 1);
