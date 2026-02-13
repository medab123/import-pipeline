export type ActivityLogViewModel = {
id: number;
logName: string;
description: string;
event: string;
subjectType: string;
subjectId: number;
causerType: string;
causerId: number;
causerName: string;
properties: Array<any>;
changes: Array<any>;
createdAt: string;
formattedCreatedAt: string;
};
export type BasicInfoStepViewModel = {
pipeline: PipelineViewModel;
frequencies: Array<any>;
stepper: CreateStepViewModel;
};
export type CreateStepViewModel = {
steps: Array<StepViewModel>;
current: StepViewModel;
};
export type DownloaderConfigStepViewModel = {
pipeline: PipelineViewModel;
stepper: CreateStepViewModel;
downloaderType: string;
config: any;
source: string;
host: string;
port: number;
username: string;
password: string;
file: string;
timeout: number;
retryAttempts: number;
headers: Array<any>;
method: string;
body: string;
queryParams: Array<any>;
verifySsl: boolean;
followRedirects: boolean;
};
export type FilterConfigStepViewModel = {
pipeline: PipelineViewModel;
stepper: CreateStepViewModel;
config: any;
rules: Array<any>;
availableOperators: { [key: string]: string };
testResult: { [key: string]: any } | null;
feedKeys: Array<any>;
};
export type ImagesPrepareConfigStepViewModel = {
pipeline: PipelineViewModel;
stepper: CreateStepViewModel;
config: any;
imageIndexesToSkip: Array<any>;
imageSeparator: string;
active: boolean;
downloadMode: string;
imagesKey: string;
feedKeys: Array<any>;
};
export type ListActivityLogViewModel = {
logs: any;
pipeline: PipelineViewModel;
paginator: PaginatorViewModel;
};
export type ListPipelineViewModel = {
pipelines: Array<PipelineViewModel>;
paginator: PaginatorViewModel;
stats: PipelineStatsViewModel;
};
export type MapperConfigStepViewModel = {
pipeline: PipelineViewModel;
stepper: CreateStepViewModel;
config: any;
fieldMappings: Array<any>;
supportsValueMapping: boolean;
availableTransformations: Array<any>;
feedKeys: Array<any>;
targetFields: Array<any>;
testResult: Array<any>;
};
export type PaginatorViewModel = {
currentPage: number;
hasMorePages: boolean;
lastPage: number;
perPage: number;
total: number;
nextPageUrl: string;
previousPageUrl: string;
};
export type PipelineStatsViewModel = {
active: number;
successful: number;
failed: number;
running: number;
total: number;
successRate: number;
failureRate: number;
};
export type PipelineViewModel = {
id: number;
name: string;
description: string;
targetId: any;
frequency: string;
startTime: string;
formattedStartTime: string;
isActive: boolean;
status: Array<any>;
createdBy: string;
updatedBy: string;
createdAt: string;
updatedAt: string;
formattedCreatedAt: string;
formattedUpdatedAt: string;
lastExecutedAt: string;
formattedLastExecutedAt: string;
nextExecutionAt: string;
formattedNextExecutionAt: string;
config: any;
};
export type PreviewStepViewModel = {
pipeline: PipelineViewModel;
stepper: CreateStepViewModel;
hasError: boolean;
error: string;
hasResult: boolean;
result: Array<any>;
previewData: Array<any>;
columns: Array<any>;
stats: Array<any>;
errors: Array<any>;
};
export type ReaderConfigStepViewModel = {
pipeline: PipelineViewModel;
stepper: CreateStepViewModel;
readerType: string;
config: any;
delimiter: string;
enclosure: string;
escape: string;
hasHeader: boolean;
trim: boolean;
entryPoint: string;
keepRoot: boolean;
testResult: Array<any>;
};
export type StepViewModel = {
step: any;
route: string;
isAvailable: boolean;
index: number;
title: string;
description: string;
};
export type TestDataMapperViewModel = {
fromArray: TestDataMapperViewModel;
fromMapperResult: TestDataMapperViewModel;
isSuccess: boolean;
getMessage: string;
getDetails: Array<any>;
testResult: Array<any>;
};
export type TestDownloaderViewModel = {
fromDownloadResult: TestDownloaderViewModel;
fromArray: TestDownloaderViewModel;
testResult: Array<any>;
isSuccess: boolean;
getMessage: string;
getDetails: Array<any>;
getFormattedResult: Array<any>;
};
export type TestFilterViewModel = {
fromFilterResult: TestFilterViewModel;
fromArray: TestFilterViewModel;
isSuccess: boolean;
getMessage: string;
testResult: Array<any>;
getDetails: Array<any>;
};
export type TestReaderViewModel = {
fromArray: TestReaderViewModel;
fromReaderResult: TestReaderViewModel;
isSuccess: boolean;
getMessage: string;
getDetails: Array<any>;
testResult: Array<any>;
};
export type ToastNotificationVariant = 'destructive' | 'default';
