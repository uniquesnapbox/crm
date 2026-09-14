<?php

namespace Tests\Unit;

use App\Http\Requests\Lead\UpdateRequest;
use Illuminate\Routing\Route;
use Tests\TestCase;

class LeadUpdateRequestRouteTest extends TestCase
{
    public function test_api_id_parameter_is_used_for_the_duplicate_exclusion(): void
    {
        $request = TestableLeadUpdateRequest::create('/api/lead-contacts/42', 'PATCH');
        $route = new Route(['PATCH'], '/api/lead-contacts/{id}', static fn () => null);
        $route->bind($request);
        $request->setRouteResolver(static fn () => $route);

        $this->assertSame(42, $request->resolvedLeadId());
    }

    public function test_web_resource_parameter_remains_supported(): void
    {
        $request = TestableLeadUpdateRequest::create('/lead-contact/43', 'PUT');
        $route = new Route(['PUT'], '/lead-contact/{lead_contact}', static fn () => null);
        $route->bind($request);
        $request->setRouteResolver(static fn () => $route);

        $this->assertSame(43, $request->resolvedLeadId());
    }
}

class TestableLeadUpdateRequest extends UpdateRequest
{
    public function resolvedLeadId(): ?int
    {
        return $this->currentLeadId();
    }
}
