<?php

namespace App\Console\Commands;

use App\Services\IncidentDetectionService;
use Illuminate\Console\Command;

class DetectIncidents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'incidents:detect {--bail-mobilite-id= : Detect incidents for a specific bail mobilité}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect incidents in bail mobilités and trigger appropriate actions';

    protected IncidentDetectionService $incidentDetectionService;

    public function __construct(IncidentDetectionService $incidentDetectionService)
    {
        parent::__construct();
        $this->incidentDetectionService = $incidentDetectionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting incident detection...');

        $bailMobiliteId = $this->option('bail-mobilite-id');

        if ($bailMobiliteId) {
            $this->detectForSpecificBailMobilite($bailMobiliteId);
        } else {
            $this->detectForAllBailMobilites();
        }

        $this->info('Incident detection completed.');
    }

    /**
     * Detect incidents for a specific bail mobilité.
     */
    protected function detectForSpecificBailMobilite(int $bailMobiliteId): void
    {
        $bailMobilite = \App\Models\BailMobilite::with([
            'entryMission.checklist',
            'exitMission.checklist',
            'entrySignature',
            'exitSignature'
        ])->find($bailMobiliteId);

        if (!$bailMobilite) {
            $this->error("Bail Mobilité with ID {$bailMobiliteId} not found.");
            return;
        }

        $this->info("Detecting incidents for Bail Mobilité #{$bailMobiliteId} - {$bailMobilite->tenant_name}");

        $incidents = $this->incidentDetectionService->detectIncidents($bailMobilite);

        if (empty($incidents)) {
            $this->info('No incidents detected.');
            return;
        }

        $this->warn("Found " . count($incidents) . " incident(s):");
        foreach ($incidents as $incident) {
            $this->line("- [{$incident['severity']}] {$incident['message']}");
        }

        $this->incidentDetectionService->processIncidents($bailMobilite, $incidents);
        $this->info('Incidents processed and notifications sent.');
    }

    /**
     * Detect incidents for all active bail mobilités.
     */
    protected function detectForAllBailMobilites(): void
    {
        $results = $this->incidentDetectionService->runIncidentDetection();

        $this->info("Processed {$results['processed']} bail mobilités");
        $this->info("Found {$results['incidents_found']} incidents");
        $this->info("Marked {$results['bail_mobilites_marked_as_incident']} bail mobilités as incident");

        if ($results['incidents_found'] > 0) {
            $this->warn("⚠️  {$results['incidents_found']} incidents detected and processed.");
        } else {
            $this->info("✅ No incidents detected.");
        }
    }
}
