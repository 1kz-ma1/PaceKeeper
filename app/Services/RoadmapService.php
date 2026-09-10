<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Task;
use Illuminate\Support\Collection;

class RoadmapService
{
    public function build(Plan $plan, ?int $currentTaskId = null, ?int $lastWorkedTaskId = null): array
    {
        $plan->loadMissing(['tasks.prerequisite']);

        $nodes = $plan->tasks
            ->sortBy(fn (Task $task) => sprintf('%010d-%010d', $task->sort_order ?? 0, $task->id))
            ->values()
            ->map(fn (Task $task) => $this->taskNode($task, $currentTaskId, $lastWorkedTaskId))
            ->all();

        return $this->summarize($nodes);
    }

    public function project(Plan $plan, array $operations): array
    {
        $plan->loadMissing(['tasks.prerequisite']);

        $tasks = $plan->tasks
            ->mapWithKeys(fn (Task $task) => [$task->id => [
                'key' => 'task-' . $task->id,
                'task_id' => $task->id,
                'client_ref' => null,
                'title' => $task->title,
                'description' => $task->description,
                'estimated_minutes' => (int) $task->estimated_minutes,
                'remaining_minutes' => (int) ($task->remaining_minutes ?? 0),
                'progress_percent' => (int) $task->progress_percent,
                'progress_reason' => $task->progress_reason,
                'next_action_note' => $task->next_action_note,
                'status' => $task->status,
                'priority' => (int) $task->priority,
                'activation_cost' => (int) ($task->activation_cost ?? 3),
                'sort_order' => (int) ($task->sort_order ?? 0),
                'depends_on_task_id' => $task->depends_on_task_id,
                'continuation_of_task_id' => $task->continuation_of_task_id,
                'source_task_ids' => $task->lineage_source_task_ids ?: ($task->continuation_of_task_id ? [(int) $task->continuation_of_task_id] : []),
                'source_task_snapshots' => $task->lineage_source_snapshots ?? [],
                'change_type' => 'unchanged',
                'operation_indexes' => [],
            ]])
            ->all();

        $newTaskRefs = [];
        $nextVirtualId = -1;
        $maxSort = collect($tasks)->max('sort_order') ?? 0;

        foreach ($operations as $index => $operation) {
            $type = $operation['type'] ?? null;

            if ($type === 'update_task' && isset($tasks[(int) ($operation['task_id'] ?? 0)])) {
                $taskId = (int) $operation['task_id'];
                foreach (['title', 'description', 'estimated_minutes', 'remaining_minutes', 'progress_percent', 'progress_reason', 'next_action_note', 'status', 'priority', 'activation_cost'] as $field) {
                    if (array_key_exists($field, $operation)) {
                        $tasks[$taskId][$field] = $operation[$field];
                    }
                }
                $tasks[$taskId]['change_type'] = 'updated';
                $tasks[$taskId]['operation_indexes'][] = (int) $index;
                continue;
            }

            if ($type === 'cancel_task' && isset($tasks[(int) ($operation['task_id'] ?? 0)])) {
                $taskId = (int) $operation['task_id'];
                $tasks[$taskId]['status'] = 'cancelled';
                $tasks[$taskId]['change_type'] = 'cancelled';
                $tasks[$taskId]['operation_indexes'][] = (int) $index;
                continue;
            }

            if ($type === 'keep_task' && isset($tasks[(int) ($operation['task_id'] ?? 0)])) {
                $taskId = (int) $operation['task_id'];
                $tasks[$taskId]['change_type'] = $tasks[$taskId]['change_type'] === 'unchanged' ? 'kept' : $tasks[$taskId]['change_type'];
                $tasks[$taskId]['operation_indexes'][] = (int) $index;
                continue;
            }

            if ($type === 'create_task') {
                $maxSort++;
                $virtualId = $nextVirtualId--;
                $clientRef = $operation['client_ref'] ?? null;
                if ($clientRef) {
                    $newTaskRefs[$clientRef] = $virtualId;
                }
                $sourceTaskIds = array_values(array_map('intval', $operation['source_task_ids'] ?? []));
                $tasks[$virtualId] = [
                    'key' => 'new-' . abs($virtualId),
                    'task_id' => null,
                    'virtual_id' => $virtualId,
                    'client_ref' => $clientRef,
                    'title' => $operation['title'] ?? '新しいTask',
                    'description' => $operation['description'] ?? null,
                    'estimated_minutes' => (int) ($operation['estimated_minutes'] ?? 0),
                    'remaining_minutes' => (int) ($operation['remaining_minutes'] ?? 0),
                    'progress_percent' => (int) ($operation['progress_percent'] ?? 0),
                    'progress_reason' => $operation['progress_reason'] ?? null,
                    'next_action_note' => $operation['next_action_note'] ?? null,
                    'status' => $operation['status'] ?? 'todo',
                    'priority' => (int) ($operation['priority'] ?? 3),
                    'activation_cost' => (int) ($operation['activation_cost'] ?? 3),
                    'sort_order' => $maxSort,
                    'depends_on_task_id' => null,
                    'continuation_of_task_id' => count($sourceTaskIds) === 1 ? $sourceTaskIds[0] : null,
                    'source_task_ids' => $sourceTaskIds,
                    'source_task_snapshots' => $operation['source_task_snapshots'] ?? [],
                    'progress_origin' => $operation['progress_origin'] ?? null,
                    'change_type' => 'created',
                    'operation_indexes' => [(int) $index],
                ];
                continue;
            }

            if ($type === 'reorder_tasks') {
                foreach (($operation['items'] ?? []) as $position => $item) {
                    $taskId = ! empty($item['task_id'])
                        ? (int) $item['task_id']
                        : ($newTaskRefs[$item['task_ref'] ?? ''] ?? null);
                    if ($taskId !== null && isset($tasks[$taskId])) {
                        $tasks[$taskId]['sort_order'] = $position + 1;
                        $tasks[$taskId]['operation_indexes'][] = (int) $index;
                        if ($tasks[$taskId]['change_type'] === 'unchanged') {
                            $tasks[$taskId]['change_type'] = 'reordered';
                        }
                    }
                }
            }
        }

        $nodes = collect($tasks)
            ->sortBy(fn (array $task) => sprintf('%010d-%010d', $task['sort_order'] ?? 0, ($task['task_id'] ?? 1000000 + abs($task['virtual_id'] ?? 0))))
            ->values()
            ->map(fn (array $task) => $this->arrayNode($task))
            ->all();

        return $this->summarize($nodes, preview: true);
    }

    private function taskNode(Task $task, ?int $currentTaskId, ?int $lastWorkedTaskId): array
    {
        return $this->arrayNode([
            'key' => 'task-' . $task->id,
            'task_id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'estimated_minutes' => (int) $task->estimated_minutes,
            'remaining_minutes' => (int) ($task->remaining_minutes ?? 0),
            'progress_percent' => (int) $task->progress_percent,
            'progress_reason' => $task->progress_reason,
            'next_action_note' => $task->next_action_note,
            'status' => $task->status,
            'priority' => (int) $task->priority,
            'activation_cost' => (int) ($task->activation_cost ?? 3),
            'sort_order' => (int) ($task->sort_order ?? 0),
            'depends_on_task_id' => $task->depends_on_task_id,
            'prerequisite_title' => $task->prerequisite?->title,
            'continuation_of_task_id' => $task->continuation_of_task_id,
            'source_task_ids' => $task->lineage_source_task_ids ?: ($task->continuation_of_task_id ? [(int) $task->continuation_of_task_id] : []),
            'source_task_snapshots' => $task->lineage_source_snapshots ?? [],
            'change_type' => 'unchanged',
            'operation_indexes' => [],
            'is_current' => $currentTaskId !== null && $task->id === $currentTaskId,
            'is_last_worked' => $lastWorkedTaskId !== null && $task->id === $lastWorkedTaskId,
        ]);
    }

    private function arrayNode(array $task): array
    {
        $status = $task['status'] ?? 'todo';
        $sourceIds = array_values(array_unique(array_map('intval', $task['source_task_ids'] ?? [])));

        return array_merge($task, [
            'status_label' => match ($status) {
                'doing' => '進行中',
                'done' => '完了',
                'cancelled' => '中止',
                default => '未着手',
            },
            'startable' => ! in_array($status, ['done', 'cancelled'], true),
            'is_current' => (bool) ($task['is_current'] ?? false),
            'is_last_worked' => (bool) ($task['is_last_worked'] ?? false),
            'is_lineage_child' => $sourceIds !== [],
            'source_task_ids' => $sourceIds,
            'operation_indexes' => array_values(array_unique(array_map('intval', $task['operation_indexes'] ?? []))),
        ]);
    }


    private function enrichLineage(array $nodes): array
    {
        $titlesById = collect($nodes)
            ->filter(fn (array $node) => ! empty($node['task_id']))
            ->mapWithKeys(fn (array $node) => [(int) $node['task_id'] => $node['title']])
            ->all();

        $childrenBySource = [];
        foreach ($nodes as $node) {
            foreach (($node['source_task_ids'] ?? []) as $sourceTaskId) {
                $sourceTaskId = (int) $sourceTaskId;
                $childrenBySource[$sourceTaskId][] = [
                    'key' => $node['key'],
                    'title' => $node['title'],
                    'status' => $node['status'],
                ];
            }
        }

        return collect($nodes)->map(function (array $node) use ($titlesById, $childrenBySource) {
            $sourceIds = $node['source_task_ids'] ?? [];
            $snapshotsById = collect($node['source_task_snapshots'] ?? [])
                ->filter(fn ($snapshot) => is_array($snapshot) && isset($snapshot['id'], $snapshot['title']))
                ->mapWithKeys(fn ($snapshot) => [(int) $snapshot['id'] => (string) $snapshot['title']])
                ->all();
            $node['source_task_titles'] = collect($sourceIds)
                ->map(fn ($id) => $snapshotsById[(int) $id] ?? $titlesById[(int) $id] ?? ('Task #' . (int) $id))
                ->values()
                ->all();

            $taskId = ! empty($node['task_id']) ? (int) $node['task_id'] : null;
            $node['lineage_children'] = $taskId !== null ? ($childrenBySource[$taskId] ?? []) : [];
            $node['is_lineage_source'] = $node['lineage_children'] !== [];

            return $node;
        })->all();
    }

    private function summarize(array $nodes, bool $preview = false): array
    {
        $nodes = $this->enrichLineage($nodes);
        $collection = collect($nodes);
        $current = $collection->firstWhere('is_current', true)
            ?? $collection->first(fn (array $node) => $node['status'] === 'doing')
            ?? $collection->first(fn (array $node) => $node['startable'] && ! $node['depends_on_task_id'])
            ?? $collection->first(fn (array $node) => $node['startable']);

        if ($current) {
            $nodes = collect($nodes)->map(function (array $node) use ($current) {
                $node['is_current'] = $node['key'] === $current['key'];
                return $node;
            })->all();
            $current = collect($nodes)->firstWhere('key', $current['key']);
        }

        return [
            'nodes' => $nodes,
            'current' => $current,
            'completed_count' => $collection->where('status', 'done')->count(),
            'active_count' => $collection->reject(fn (array $node) => in_array($node['status'], ['done', 'cancelled'], true))->count(),
            'preview' => $preview,
        ];
    }
}
