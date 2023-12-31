<?php

namespace App\Http\Middleware;

use App\Utils\Common\MessageFactory;
use App\Utils\Common\RequestService;
use App\Utils\Reflection\Action;
use App\Utils\Reflection\AnnotationBadKeyException;
use App\Utils\Reflection\AnnotationBadScopeException;
use App\Utils\Reflection\AnnotationNotFoundException;
use App\Utils\Reflection\AnnotationSyntaxException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ReflectionException;

class RuleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws AnnotationBadKeyException
     * @throws AnnotationBadScopeException
     * @throws AnnotationSyntaxException
     * @throws ReflectionException
     */
    public function handle($request, Closure $next)
    {
        $method = Action::withRequest($request)->getMethod();
        try {
            $rules = $method->getAnnotation("rules")->getProperties();
            if (isset($rules["dynamic_rules"])) {
                $dynamicRules = $rules["dynamic_rules"];
                unset($rules["dynamic_rules"]);
                $rules = array_merge($rules, $dynamicRules);
            }
        } catch (AnnotationNotFoundException $e) {
            return $next($request);
        }

        $disabled_rules = explode(",", env("TEMPORARILY_DISABLED_RULES", ""));
        foreach ($disabled_rules as $disabled_rule) {
            if (key_exists($disabled_rule, $rules)) {
                unset($rules[$disabled_rule]);
            }
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            if (RequestService::isRequestAjax($request)) {
                return response()->json(
                    MessageFactory::createWithValidationMessages(
                        $validator->messages()->toArray(),
                        400, [
                        "request_data" => $request->all()
                    ]), 400);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }
        return $next($request);
    }
}
