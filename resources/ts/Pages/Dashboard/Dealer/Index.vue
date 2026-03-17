<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref, watch } from "vue";
import { useDebounceFn } from "@vueuse/core";
import Default from "@/components/Layoute/Default.vue";
import { PageHeader } from "@/components/ui/page-header";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    TableEmpty,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Pagination } from "@/components/ui/pagination";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Plus,
    MoreHorizontal,
    Edit,
    Trash2,
    Search,
    Eye,
    X,
    Store,
    DatabaseZap,
} from "lucide-vue-next";

interface DealerItem {
    id: number;
    name: string;
    status: string;
    postingAddress: string | null;
    websiteUrl: string | null;
    paymentPeriod: string;
    transactionsCount: number;
    scrapsCount: number;
    isPaid: boolean;
    hasFbmpToken: boolean;
    hasScrapSource: boolean;
    formattedCreatedAt: string;
}

interface Props {
    dealers: DealerItem[];
    paginator: {
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
        nextPageUrl: string | null;
        previousPageUrl: string | null;
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || "");
const performSearch = useDebounceFn(() => {
    router.get(
        route("dashboard.dealers.index"),
        { search: searchQuery.value },
        { preserveState: true, replace: true },
    );
}, 300);

watch(searchQuery, performSearch);

const clearSearch = () => {
    searchQuery.value = "";
};

const deleteDialogOpen = ref(false);
const dealerToDelete = ref<DealerItem | null>(null);

const openDeleteDialog = (dealer: DealerItem) => {
    dealerToDelete.value = dealer;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!dealerToDelete.value) return;
    router.delete(route("dashboard.dealers.destroy", dealerToDelete.value.id), {
        onSuccess: () => {
            deleteDialogOpen.value = false;
            dealerToDelete.value = null;
        },
    });
};

// Add Scrap Source dialog
const scrapDialogOpen = ref(false);
const scrapDealerTarget = ref<DealerItem | null>(null);

const scrapForm = useForm({
    dealer_id: "" as string | number,
    ftp_file_path: "",
    provider: "",
});

const openScrapDialog = (dealer: DealerItem) => {
    scrapDealerTarget.value = dealer;
    scrapForm.reset();
    scrapForm.clearErrors();
    scrapForm.dealer_id = dealer.id;
    scrapDialogOpen.value = true;
};

const submitScrapSource = () => {
    scrapForm.post(route("dashboard.scraps.store"), {
        onSuccess: () => {
            scrapDialogOpen.value = false;
            scrapDealerTarget.value = null;
        },
    });
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case "active":
            return "default";
        case "pending":
            return "outline";
        case "inactive":
            return "secondary";
        default:
            return "secondary";
    }
};
</script>

<template>
    <Head title="Dealers" />

    <Default>
        <PageHeader
            title="Dealers"
            description="Manage your dealer network and partnerships."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="route('dashboard.dealers.create')">
                        <Plus class="w-4 h-4 mr-2" />
                        Add Dealer
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <Card class="shadow-sm">
            <CardHeader class="pb-4 border-b">
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <CardTitle class="text-xl font-bold"
                            >All Dealers</CardTitle
                        >
                        <CardDescription>
                            A list of all dealers in your organization.
                        </CardDescription>
                    </div>
                    <div class="relative w-full max-w-sm">
                        <Search
                            class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground"
                        />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search dealers..."
                            class="pl-9 pr-9"
                        />
                        <button
                            v-if="searchQuery"
                            @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </CardHeader>
            <CardContent class="pt-6">
                <div class="overflow-x-auto rounded-lg border bg-background">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Payment Status</TableHead>
                                <TableHead>Period</TableHead>
                                <TableHead>Transactions</TableHead>
                                <TableHead>Scraps</TableHead>
                                <TableHead class="text-right"
                                    >Actions</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty
                                v-if="dealers.length === 0"
                                :colspan="7"
                            >
                                <div class="text-center py-12">
                                    <div
                                        class="bg-muted/50 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4"
                                    >
                                        <Store
                                            class="w-6 h-6 text-muted-foreground"
                                        />
                                    </div>
                                    <h3 class="font-semibold text-lg">
                                        No dealers found
                                    </h3>
                                    <p
                                        class="text-muted-foreground text-sm mt-1 mb-4"
                                    >
                                        Start by adding your first dealer.
                                    </p>
                                    <Button as-child variant="outline">
                                        <Link
                                            :href="
                                                route(
                                                    'dashboard.dealers.create',
                                                )
                                            "
                                        >
                                            Create Dealer
                                        </Link>
                                    </Button>
                                </div>
                            </TableEmpty>
                            <TableRow
                                v-for="dealer in dealers"
                                :key="dealer.id"
                            >
                                <TableCell class="font-medium">{{
                                    dealer.name
                                }}</TableCell>
                                <TableCell>
                                    <div class="flex flex-col gap-1">
                                        <Badge
                                            :variant="
                                                getStatusVariant(dealer.status)
                                            "
                                            >{{ dealer.status }}</Badge
                                        >
                                        <div
                                            v-if="dealer.status === 'pending'"
                                            class="text-xs text-muted-foreground"
                                        >
                                            <span v-if="!dealer.hasFbmpToken && !dealer.hasScrapSource">
                                                Missing FBMP token & scrap source
                                            </span>
                                            <span v-else-if="!dealer.hasFbmpToken">
                                                Missing FBMP token
                                            </span>
                                            <span v-else-if="!dealer.hasScrapSource">
                                                Missing scrap source
                                            </span>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="
                                            dealer.isPaid
                                                ? 'default'
                                                : 'destructive'
                                        "
                                    >
                                        {{ dealer.isPaid ? "Paid" : "Unpaid" }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">{{
                                        dealer.paymentPeriod
                                    }}</Badge>
                                </TableCell>
                                <TableCell>{{
                                    dealer.transactionsCount
                                }}</TableCell>
                                <TableCell>{{ dealer.scrapsCount }}</TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    route(
                                                        'dashboard.dealers.show',
                                                        dealer.id,
                                                    )
                                                "
                                            >
                                                <Eye class="w-4 h-4" />
                                                <span class="sr-only"
                                                    >View</span
                                                >
                                            </Link>
                                        </Button>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <MoreHorizontal
                                                        class="w-4 h-4"
                                                    />
                                                    <span class="sr-only"
                                                        >Menu</span
                                                    >
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem as-child>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'dashboard.dealers.edit',
                                                                dealer.id,
                                                            )
                                                        "
                                                    >
                                                        <Edit
                                                            class="w-4 h-4 mr-2"
                                                        />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    :disabled="dealer.hasScrapSource"
                                                    @click="
                                                        !dealer.hasScrapSource && openScrapDialog(dealer)
                                                    "
                                                >
                                                    <DatabaseZap
                                                        class="w-4 h-4 mr-2"
                                                    />
                                                    Add Scrap Source
                                                    <span
                                                        v-if="dealer.hasScrapSource"
                                                        class="ml-auto text-xs text-muted-foreground"
                                                    >Added</span>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    class="text-destructive"
                                                    @click="
                                                        openDeleteDialog(dealer)
                                                    "
                                                >
                                                    <Trash2
                                                        class="w-4 h-4 mr-2"
                                                    />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="paginator.total > paginator.perPage" class="mt-4">
                    <Pagination :paginator="paginator" />
                </div>
            </CardContent>
        </Card>

        <!-- Delete Dealer Dialog -->
        <AlertDialog v-model:open="deleteDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Dealer?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to delete
                        <span class="font-semibold">{{
                            dealerToDelete?.name
                        }}</span
                        >? This will also delete all related transactions and
                        scrap sources.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        @click="confirmDelete"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Add Scrap Source Dialog -->
        <Dialog v-model:open="scrapDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add Scrap Source</DialogTitle>
                    <DialogDescription>
                        Add a new scrap source for
                        <span class="font-semibold">{{ scrapDealerTarget?.name }}</span>.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitScrapSource" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="scrap_provider">Provider</Label>
                        <Input
                            id="scrap_provider"
                            v-model="scrapForm.provider"
                            placeholder="e.g. AutoTrader, Cars.com"
                            :class="{ 'border-destructive': scrapForm.errors.provider }"
                        />
                        <p v-if="scrapForm.errors.provider" class="text-sm text-destructive">
                            {{ scrapForm.errors.provider }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="scrap_ftp_file_path">FTP File Path</Label>
                        <Input
                            id="scrap_ftp_file_path"
                            v-model="scrapForm.ftp_file_path"
                            placeholder="/data/feeds/dealer_feed.csv"
                            class="font-mono text-sm"
                            :class="{ 'border-destructive': scrapForm.errors.ftp_file_path }"
                        />
                        <p v-if="scrapForm.errors.ftp_file_path" class="text-sm text-destructive">
                            {{ scrapForm.errors.ftp_file_path }}
                        </p>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="scrapDialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="scrapForm.processing">
                            {{ scrapForm.processing ? "Saving..." : "Add Scrap Source" }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </Default>
</template>
