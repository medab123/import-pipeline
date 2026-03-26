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
export type CreateDealerViewModel = {
paymentPeriods: Array<any>;
};
export type CreatePaymentTransactionViewModel = {
dealers: Array<any>;
types: Array<any>;
statuses: Array<any>;
};
export type CreateScrapViewModel = {
dealers: Array<any>;
};
export type CreateStepViewModel = {
steps: Array<StepViewModel>;
current: StepViewModel;
};
export type DealerStatus = 'pending' | 'active' | 'inactive';
export type DealerViewModel = {
id: number;
name: string;
status: string;
notes: string;
postingAddress: string;
websiteUrls: Array<any>;
fbmpAppAccessToken: string;
fbmpAppUrl: string;
paymentPeriod: string;
createdAt: string;
updatedAt: string;
formattedCreatedAt: string;
formattedUpdatedAt: string;
transactionsCount: number;
scrapsCount: number;
isPaid: boolean;
hasFbmpToken: boolean;
hasScrapSource: boolean;
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
export type EditDealerViewModel = {
dealer: DealerViewModel;
paymentPeriods: Array<any>;
};
export type EditPaymentTransactionViewModel = {
transaction: PaymentTransactionViewModel;
dealers: Array<any>;
types: Array<any>;
statuses: Array<any>;
};
export type EditScrapViewModel = {
scrap: ScrapViewModel;
dealers: Array<any>;
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
targetFields: Array<any>;
};
export type ListActivityLogViewModel = {
logs: any;
pipeline: PipelineViewModel;
paginator: PaginatorViewModel;
};
export type ListDealerViewModel = {
dealers: Array<DealerViewModel>;
paginator: PaginatorViewModel;
filters: Array<any>;
};
export type ListPaymentTransactionViewModel = {
transactions: Array<PaymentTransactionViewModel>;
paginator: PaginatorViewModel;
filters: Array<any>;
};
export type ListPipelineViewModel = {
pipelines: Array<PipelineViewModel>;
paginator: PaginatorViewModel;
stats: PipelineStatsViewModel;
};
export type ListScrapViewModel = {
scraps: Array<ScrapViewModel>;
paginator: PaginatorViewModel;
filters: Array<any>;
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
export type PaymentPeriod = 'month' | 'year';
export type PaymentTransactionStatus = 'pending' | 'completed' | 'failed' | 'refunded';
export type PaymentTransactionType = 'dealer_payment' | 'fbmp_payment';
export type PaymentTransactionViewModel = {
id: number;
dealerId: number;
dealerName: string;
type: string;
amount: string;
status: string;
paymentMethod: string;
reference: string;
paidAt: string;
formattedPaidAt: string;
createdAt: string;
formattedCreatedAt: string;
};
export type PipelineStatsViewModel = {
total: number;
active: number;
inactive: number;
needsConfiguration: number;
};
export type PipelineViewModel = {
id: number;
name: string;
description: string;
targetId: string | number;
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
export type ScrapViewModel = {
id: number;
dealerId: number;
dealerName: string;
ftpFilePath: string;
provider: string;
createdAt: string;
formattedCreatedAt: string;
updatedAt: string;
formattedUpdatedAt: string;
};
export type ShowDealerViewModel = {
dealer: DealerViewModel;
recentTransactions: Array<any>;
scraps: Array<any>;
importPipelines: Array<any>;
};
export type ShowPaymentTransactionViewModel = {
transaction: PaymentTransactionViewModel;
};
export type ShowScrapViewModel = {
scrap: ScrapViewModel;
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
