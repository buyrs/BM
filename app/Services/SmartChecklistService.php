<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\PropertyCondition;
use Illuminate\Support\Collection;

class SmartChecklistService extends BaseService
{
    /**
     * Standard checklist templates by property type.
     */
    private array $templates = [
        'apartment' => [
            'Entrée' => ['Porte d\'entrée', 'Sonnette', 'Boîte aux lettres', 'Éclairage'],
            'Salon' => ['Murs', 'Sol', 'Plafond', 'Fenêtres', 'Volets', 'Prises électriques'],
            'Cuisine' => ['Plan de travail', 'Évier', 'Robinetterie', 'Four', 'Plaques', 'Hotte', 'Réfrigérateur', 'Placards'],
            'Chambre' => ['Murs', 'Sol', 'Plafond', 'Fenêtres', 'Volets', 'Prises électriques', 'Placard'],
            'Salle de bain' => ['Lavabo', 'Robinetterie', 'Douche/Baignoire', 'WC', 'Carrelage', 'Ventilation', 'Miroir'],
            'Extérieur' => ['Balcon', 'Terrasse', 'Store'],
        ],
        'house' => [
            'Entrée' => ['Porte d\'entrée', 'Sonnette', 'Boîte aux lettres', 'Éclairage'],
            'Salon' => ['Murs', 'Sol', 'Plafond', 'Fenêtres', 'Volets', 'Prises électriques', 'Cheminée'],
            'Cuisine' => ['Plan de travail', 'Évier', 'Robinetterie', 'Four', 'Plaques', 'Hotte', 'Réfrigérateur', 'Placards'],
            'Chambre 1' => ['Murs', 'Sol', 'Plafond', 'Fenêtres', 'Volets', 'Prises électriques', 'Placard'],
            'Chambre 2' => ['Murs', 'Sol', 'Plafond', 'Fenêtres', 'Volets', 'Prises électriques', 'Placard'],
            'Salle de bain' => ['Lavabo', 'Robinetterie', 'Douche/Baignoire', 'WC', 'Carrelage', 'Ventilation', 'Miroir'],
            'Garage' => ['Porte', 'Sol', 'Éclairage', 'Prises électriques'],
            'Jardin' => ['Pelouse', 'Clôture', 'Portail', 'Terrasse', 'Arrosage'],
        ],
        'commercial' => [
            'Façade' => ['Vitrine', 'Enseigne', 'Porte d\'entrée', 'Éclairage'],
            'Espace principal' => ['Murs', 'Sol', 'Plafond', 'Fenêtres', 'Prises électriques', 'Climatisation'],
            'Sanitaires' => ['Lavabo', 'WC', 'Carrelage', 'Ventilation'],
            'Arrière-boutique' => ['Murs', 'Sol', 'Éclairage', 'Accès'],
            'Sécurité' => ['Extincteur', 'Alarme', 'Issue de secours'],
        ],
    ];

    /**
     * Generate a smart checklist for a mission.
     */
    public function generateForMission(Mission $mission): Checklist
    {
        $property = $mission->property;
        $propertyType = $property->type ?? 'apartment';
        
        // Get base template
        $template = $this->templates[$propertyType] ?? $this->templates['apartment'];
        
        // Enhance with condition-based items
        $enhancedTemplate = $this->enhanceWithConditions($property, $template);
        
        // Create checklist
        $checklist = Checklist::create([
            'mission_id' => $mission->id,
            'name' => 'État des lieux - ' . $property->name,
            'type' => 'smart',
        ]);

        // Create items
        $order = 0;
        foreach ($enhancedTemplate as $area => $items) {
            foreach ($items as $item) {
                ChecklistItem::create([
                    'checklist_id' => $checklist->id,
                    'area' => $area,
                    'description' => $item['description'],
                    'order' => $order++,
                    'is_required' => $item['required'] ?? true,
                    'requires_photo' => $item['requires_photo'] ?? false,
                    'previous_condition' => $item['previous_condition'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }
        }

        return $checklist;
    }

    /**
     * Enhance template with condition-based items.
     */
    private function enhanceWithConditions(Property $property, array $template): array
    {
        $enhanced = [];
        
        // Get current conditions for property
        $conditionService = app(ConditionTrackingService::class);
        $currentConditions = $conditionService->getCurrentConditions($property)
            ->keyBy(fn($c) => "{$c->area}:{$c->item}");

        foreach ($template as $area => $items) {
            $enhanced[$area] = [];
            
            foreach ($items as $item) {
                $key = "{$area}:{$item}";
                $condition = $currentConditions[$key] ?? null;

                $itemData = [
                    'description' => $item,
                    'required' => true,
                    'requires_photo' => false,
                    'previous_condition' => $condition?->condition,
                    'notes' => null,
                ];

                // Add special handling for poor/critical conditions
                if ($condition && in_array($condition->condition, ['poor', 'critical'])) {
                    $itemData['requires_photo'] = true;
                    $itemData['notes'] = "⚠️ Précédent état: {$condition->condition}. Photo obligatoire.";
                }

                $enhanced[$area][] = $itemData;
            }
        }

        return $enhanced;
    }

    /**
     * Get available templates.
     */
    public function getTemplates(): array
    {
        return array_keys($this->templates);
    }

    /**
     * Get template details.
     */
    public function getTemplate(string $type): ?array
    {
        return $this->templates[$type] ?? null;
    }

    /**
     * Create custom template.
     */
    public function createCustomTemplate(string $name, array $areas): void
    {
        $this->templates[$name] = $areas;
    }

    /**
     * Suggest additional items based on property history.
     */
    public function suggestAdditionalItems(Property $property): array
    {
        $suggestions = [];
        
        // Get degraded conditions from recent inspections
        $conditionService = app(ConditionTrackingService::class);
        $degraded = $conditionService->getDegradedConditions($property, now()->subMonths(6));

        foreach ($degraded as $condition) {
            $suggestions[] = [
                'area' => $condition->area,
                'item' => $condition->item,
                'reason' => "État dégradé lors de l'inspection du " . $condition->recorded_at->format('d/m/Y'),
                'priority' => 'high',
            ];
        }

        // Get items needing attention
        $critical = $conditionService->getAreasNeedingAttention($property);

        foreach ($critical as $area => $items) {
            foreach ($items as $item) {
                $key = "{$area}:{$item->item}";
                if (!collect($suggestions)->contains(fn($s) => "{$s['area']}:{$s['item']}" === $key)) {
                    $suggestions[] = [
                        'area' => $area,
                        'item' => $item->item,
                        'reason' => "État actuel: {$item->condition}",
                        'priority' => $item->condition === 'critical' ? 'critical' : 'high',
                    ];
                }
            }
        }

        return $suggestions;
    }

    /**
     * Update checklist from mission completion to record conditions.
     */
    public function recordConditionsFromChecklist(Checklist $checklist): void
    {
        $mission = $checklist->mission;
        $property = $mission->property;
        $conditionService = app(ConditionTrackingService::class);

        foreach ($checklist->items as $item) {
            if (!empty($item->data['condition'])) {
                $conditionService->recordCondition(
                    property: $property,
                    area: $item->area ?? 'General',
                    item: $item->description,
                    condition: $item->data['condition'],
                    mission: $mission,
                    recordedBy: $mission->checker_id,
                    notes: $item->notes,
                    photoPath: $item->photo_path
                );
            }
        }
    }
}
