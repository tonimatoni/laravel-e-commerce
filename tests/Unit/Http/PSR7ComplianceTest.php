<?php

namespace Tests\Unit\Http;

use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class PSR7ComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_controller_uses_psr7_compatible_request_types(): void
    {
        $reflection = new ReflectionClass(CartController::class);
        
        $storeMethod = $reflection->getMethod('store');
        $updateMethod = $reflection->getMethod('update');
        
        $storeParams = $storeMethod->getParameters();
        $updateParams = $updateMethod->getParameters();
        
        $this->assertCount(1, $storeParams);
        $this->assertStringContainsString('Request', $storeParams[0]->getType()->getName());
        
        $this->assertCount(2, $updateParams);
        $this->assertStringContainsString('Request', $updateParams[0]->getType()->getName());
    }

    public function test_cart_controller_returns_psr7_compatible_response_types(): void
    {
        $reflection = new ReflectionClass(CartController::class);
        
        $indexMethod = $reflection->getMethod('index');
        $storeMethod = $reflection->getMethod('store');
        
        $indexReturnType = $indexMethod->getReturnType();
        $storeReturnType = $storeMethod->getReturnType();
        
        $this->assertEquals(Response::class, $indexReturnType->getName());
        $this->assertEquals(RedirectResponse::class, $storeReturnType->getName());
    }

    public function test_product_controller_uses_psr7_compatible_response_types(): void
    {
        $reflection = new ReflectionClass(ProductController::class);
        
        $indexMethod = $reflection->getMethod('index');
        $returnType = $indexMethod->getReturnType();
        
        $this->assertEquals(Response::class, $returnType->getName());
    }

    public function test_controllers_use_illuminate_http_request(): void
    {
        $controllers = [
            CartController::class,
            ProductController::class,
        ];
        
        foreach ($controllers as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            
            foreach ($methods as $method) {
                if ($method->isConstructor()) {
                    continue;
                }
                
                $params = $method->getParameters();
                foreach ($params as $param) {
                    $type = $param->getType();
                    if ($type && $type->getName() !== 'string' && $type->getName() !== 'int') {
                        $typeName = $type->getName();
                        
                        if (str_contains($typeName, 'Request')) {
                            $this->assertTrue(
                                str_contains($typeName, 'Illuminate') || 
                                str_contains($typeName, 'App\\Http\\Requests'),
                                "Controller {$controllerClass}::{$method->getName()} uses non-Laravel request type: {$typeName}"
                            );
                        }
                    }
                }
            }
        }
    }

    public function test_controllers_return_psr7_compatible_responses(): void
    {
        $controllers = [
            CartController::class,
            ProductController::class,
        ];
        
        foreach ($controllers as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            
            foreach ($methods as $method) {
                if ($method->isConstructor()) {
                    continue;
                }
                
                $returnType = $method->getReturnType();
                if ($returnType) {
                    $returnTypeName = $returnType->getName();
                    
                    $this->assertTrue(
                        $returnTypeName === Response::class ||
                        $returnTypeName === RedirectResponse::class ||
                        str_contains($returnTypeName, 'Response'),
                        "Controller {$controllerClass}::{$method->getName()} returns non-standard response type: {$returnTypeName}"
                    );
                }
            }
        }
    }

    public function test_form_requests_extend_illuminate_form_request(): void
    {
        $formRequests = [
            \App\Http\Requests\AddToCartRequest::class,
            \App\Http\Requests\UpdateCartRequest::class,
            \App\Http\Requests\Auth\LoginRequest::class,
            \App\Http\Requests\Auth\RegisterRequest::class,
        ];
        
        foreach ($formRequests as $formRequestClass) {
            $reflection = new ReflectionClass($formRequestClass);
            $parent = $reflection->getParentClass();
            
            $this->assertNotNull($parent, "Form Request {$formRequestClass} should extend FormRequest");
            $this->assertStringContainsString('FormRequest', $parent->getName());
        }
    }
}
