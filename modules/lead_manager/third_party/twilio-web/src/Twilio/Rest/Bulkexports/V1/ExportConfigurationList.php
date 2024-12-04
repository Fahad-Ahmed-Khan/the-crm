<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Bulkexports\V1;

use Twilio\ListResource;
use Twilio\Version;

/**
 * PLEASE NOTE that this class contains beta products that are subject to change. Use them with caution.
 */
class ExportConfigurationList extends ListResource {
    /**
     * Construct the ExportConfigurationList
     *
     * @param Version $version Version that contains the resource
     */
    public function __construct(Version $version) {
        parent::__construct($version);

        // Path Solution
        $this->solution = [];
    }

    /**
     * Constructs a ExportConfigurationContext
     *
     * @param string $resourceType The type of communication – Messages, Calls
     */
    public function getContext(string $resourceType): ExportConfigurationContext {
        return new ExportConfigurationContext($this->version, $resourceType);
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string {
        return '[Twilio.Bulkexports.V1.ExportConfigurationList]';
    }
}