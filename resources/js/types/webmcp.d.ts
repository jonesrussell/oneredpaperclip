/**
 * WebMCP (Model Context Protocol) browser API types.
 * @see https://webmachinelearning.github.io/webmcp/
 */

export interface ToolAnnotations {
    readOnlyHint?: boolean;
}

export type ToolExecuteCallback = (
    input: Record<string, unknown>,
    client: ModelContextClient,
) => Promise<unknown>;

export type UserInteractionCallback = () => Promise<unknown>;

export interface ModelContextTool {
    name: string;
    description: string;
    inputSchema?: Record<string, unknown>;
    execute: ToolExecuteCallback;
    annotations?: ToolAnnotations;
}

export interface ModelContextOptions {
    tools?: ModelContextTool[];
}

export interface ModelContextClient {
    requestUserInteraction(callback: UserInteractionCallback): Promise<unknown>;
}

export interface ModelContext {
    provideContext(options?: ModelContextOptions): void;
    clearContext(): void;
    registerTool(tool: ModelContextTool): void;
    unregisterTool(name: string): void;
}

declare global {
    interface Navigator {
        modelContext?: ModelContext;
    }
}

export {};
