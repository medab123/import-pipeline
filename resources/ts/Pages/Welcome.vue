<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import ModeToggle from '@/components/ModeToggle.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import {
  ArrowRight,
  Database,
  BarChart3,
  Zap,
  CheckCircle2,
  FileCode,
  ShieldCheck,
  Cpu
} from 'lucide-vue-next'

defineProps<{
  canLogin?: boolean
  canRegister?: boolean
  laravelVersion: string
  phpVersion: string
}>()
</script>

<template>
  <Head title="Welcome" />

  <div class="min-h-screen bg-background text-foreground flex flex-col">
    <!-- Navbar -->
    <header class="border-b sticky top-0 z-40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
      <div class="container mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2 font-bold text-xl">
          <div class="h-8 w-8 bg-primary rounded-lg flex items-center justify-center text-primary-foreground">
            <Database class="h-5 w-5" />
          </div>
          ImportPipeline
        </div>
        
        <nav class="flex items-center gap-4">
          <ModeToggle />
          <template v-if="$page.props.auth.user">
            <Button as-child variant="default">
              <Link :href="route('dashboard')">Dashboard</Link>
            </Button>
          </template>
          <template v-else>
            <Button as-child variant="ghost">
              <Link :href="route('login')">Log in</Link>
            </Button>
            <Button as-child>
              <Link :href="route('register')">Get Started</Link>
            </Button>
          </template>
        </nav>
      </div>
    </header>

    <main class="flex-1">
      <!-- Hero Section -->
      <section class="py-20 md:py-32 relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px]"></div>
        <div class="container mx-auto px-4 text-center">
          <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80 mb-6">
            <span class="flex h-2 w-2 rounded-full bg-primary mr-2"></span>
            v1.0 Now Available
          </div>
          <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight mb-6 bg-gradient-to-r from-foreground to-foreground/70 bg-clip-text text-transparent pb-2">
            Supercharge Your Data Import Workflow
          </h1>
          <p class="text-xl text-muted-foreground max-w-2xl mx-auto mb-10">
            Automate, validate, and process your data imports with our powerful pipeline engine. 
            Handle millions of records with ease and precision.
          </p>
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <Button size="lg" class="h-12 px-8 text-lg" as-child>
              <Link :href="route('register')">
                Start Free Trial
                <ArrowRight class="ml-2 h-5 w-5" />
              </Link>
            </Button>
            <Button size="lg" variant="outline" class="h-12 px-8 text-lg">
              View Documentation
            </Button>
          </div>
          
          <!-- Mockup -->
          <div class="mt-16 mx-auto max-w-5xl rounded-xl border bg-card shadow-2xl p-2 relative overflow-hidden">
             <!-- Simulated Browser Window -->
             <div class="bg-muted/50 rounded-lg aspect-auto md:aspect-[16/9] flex flex-col overflow-hidden">
                <div class="h-8 bg-muted border-b flex items-center px-4 gap-2">
                   <div class="h-3 w-3 rounded-full bg-red-400/50"></div>
                   <div class="h-3 w-3 rounded-full bg-yellow-400/50"></div>
                   <div class="h-3 w-3 rounded-full bg-green-400/50"></div>
                </div>
                <!-- Simulated Interface -->
                <div class="p-6 md:p-8 flex-1 flex flex-col gap-6">
                    <div class="flex items-center justify-between">
                         <div class="space-y-1">
                             <div class="h-6 w-48 bg-foreground/10 rounded"></div>
                             <div class="h-4 w-32 bg-foreground/5 rounded"></div>
                         </div>
                         <div class="h-10 w-32 bg-primary/20 rounded"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="h-32 rounded-lg bg-card border shadow-sm p-4 space-y-3">
                             <div class="h-8 w-8 rounded bg-primary/10"></div>
                             <div class="h-4 w-24 bg-foreground/10 rounded"></div>
                             <div class="h-6 w-16 bg-foreground/20 rounded"></div>
                        </div>
                        <div class="h-32 rounded-lg bg-card border shadow-sm p-4 space-y-3">
                             <div class="h-8 w-8 rounded bg-primary/10"></div>
                             <div class="h-4 w-24 bg-foreground/10 rounded"></div>
                             <div class="h-6 w-16 bg-foreground/20 rounded"></div>
                        </div>
                        <div class="h-32 rounded-lg bg-card border shadow-sm p-4 space-y-3">
                             <div class="h-8 w-8 rounded bg-primary/10"></div>
                             <div class="h-4 w-24 bg-foreground/10 rounded"></div>
                             <div class="h-6 w-16 bg-foreground/20 rounded"></div>
                        </div>
                    </div>
                    <div class="flex-1 rounded-lg border bg-card shadow-sm p-4">
                         <div class="space-y-4">
                              <div class="h-10 w-full bg-muted/50 rounded flex items-center px-4 gap-4">
                                  <div class="h-4 w-4 bg-muted-foreground/20 rounded"></div>
                                  <div class="h-4 w-24 bg-muted-foreground/20 rounded"></div>
                              </div>
                              <div class="space-y-2">
                                  <div class="h-12 w-full border-b flex items-center px-4 gap-4">
                                      <div class="h-4 w-4 bg-muted-foreground/10 rounded"></div>
                                      <div class="h-4 w-full bg-muted-foreground/10 rounded"></div>
                                  </div>
                                  <div class="h-12 w-full border-b flex items-center px-4 gap-4">
                                      <div class="h-4 w-4 bg-muted-foreground/10 rounded"></div>
                                      <div class="h-4 w-full bg-muted-foreground/10 rounded"></div>
                                  </div>
                              </div>
                         </div>
                    </div>
                </div>
             </div>
          </div>
        </div>
      </section>
      
      <!-- Tabs Section -->
      <section class="py-20 bg-muted/50">
        <div class="container mx-auto px-4">
          <div class="text-center mb-16">
            <h2 class="text-3xl font-bold tracking-tight mb-4">Built for complex data workflows</h2>
            <p class="text-muted-foreground max-w-2xl mx-auto">
              Everything you need to ingest, transform, and load data efficiently.
            </p>
          </div>

          <Tabs default-value="import" class="w-full max-w-4xl mx-auto">
            <TabsList class="grid w-full grid-cols-3 mb-8">
              <TabsTrigger value="import">Import</TabsTrigger>
              <TabsTrigger value="validate">Validate</TabsTrigger>
              <TabsTrigger value="process">Process</TabsTrigger>
            </TabsList>
            <TabsContent value="import" class="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center gap-2">
                    <FileCode class="h-5 w-5 text-primary" />
                    Universal Format Support
                  </CardTitle>
                  <CardDescription>
                    Drag and drop your CSV, Excel, XML, or JSON files. We handle the parsing automatically.
                  </CardDescription>
                </CardHeader>
                <CardContent class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <ul class="space-y-2">
                            <li class="flex items-center gap-2 text-sm text-foreground">
                                <CheckCircle2 class="h-4 w-4 text-primary" /> Automatic encoding detection
                            </li>
                            <li class="flex items-center gap-2 text-sm text-foreground">
                                <CheckCircle2 class="h-4 w-4 text-primary" /> Large file streaming (up to 5GB)
                            </li>
                             <li class="flex items-center gap-2 text-sm text-foreground">
                                <CheckCircle2 class="h-4 w-4 text-primary" /> Custom delimiter support
                            </li>
                        </ul>
                    </div>
                     <div class="bg-muted rounded-lg p-4 flex items-center justify-center">
                        <div class="text-center space-y-2 text-muted-foreground">
                            <FileCode class="h-8 w-8 mx-auto opacity-50" />
                            <p class="text-xs">Supported: .csv, .xls, .xlsx, .json, .xml</p>
                        </div>
                     </div>
                </CardContent>
              </Card>
            </TabsContent>
            <TabsContent value="validate">
               <Card>
                <CardHeader>
                  <CardTitle class="flex items-center gap-2">
                    <ShieldCheck class="h-5 w-5 text-primary" />
                    Intelligent Validation
                  </CardTitle>
                  <CardDescription>
                    Define strict schemas and rules. Catch bad data before it corrupts your database.
                  </CardDescription>
                </CardHeader>
                <CardContent class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <ul class="space-y-2">
                            <li class="flex items-center gap-2 text-sm text-foreground">
                                <CheckCircle2 class="h-4 w-4 text-primary" /> Regex & type checking
                            </li>
                            <li class="flex items-center gap-2 text-sm text-foreground">
                                <CheckCircle2 class="h-4 w-4 text-primary" /> Foreign key lookups
                            </li>
                             <li class="flex items-center gap-2 text-sm text-foreground">
                                <CheckCircle2 class="h-4 w-4 text-primary" /> Custom logic hooks
                            </li>
                        </ul>
                    </div>
                     <div class="bg-muted rounded-lg p-4 flex items-center justify-center">
                         <div class="space-y-2 w-full max-w-[200px]">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-red-500">Row 142</span>
                                <span class="bg-red-100 text-red-600 px-1 rounded">Invalid Email</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-green-500">Row 143</span>
                                <span class="bg-green-100 text-green-600 px-1 rounded">Valid</span>
                            </div>
                         </div>
                     </div>
                </CardContent>
              </Card>
            </TabsContent>
            <TabsContent value="process">
               <Card>
                <CardHeader>
                  <CardTitle class="flex items-center gap-2">
                    <Cpu class="h-5 w-5 text-primary" />
                    High-Performance Engine
                  </CardTitle>
                  <CardDescription>
                    Our job queues process thousands of records in parallel.
                  </CardDescription>
                </CardHeader>
                <CardContent class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <ul class="space-y-2">
                            <li class="flex items-center gap-2 text-sm text-foreground">
                                <CheckCircle2 class="h-4 w-4 text-primary" /> Horizontal scaling
                            </li>
                            <li class="flex items-center gap-2 text-sm text-foreground">
                                <CheckCircle2 class="h-4 w-4 text-primary" /> Rate limiting & throttling
                            </li>
                             <li class="flex items-center gap-2 text-sm text-foreground">
                                <CheckCircle2 class="h-4 w-4 text-primary" /> Real-time progress tracking
                            </li>
                        </ul>
                    </div>
                     <div class="bg-muted rounded-lg p-4 flex items-center justify-center">
                        <div class="w-full space-y-2">
                           <div class="h-2 w-full bg-muted-foreground/10 rounded overflow-hidden">
                               <div class="h-full bg-primary w-[75%]"></div>
                           </div>
                           <p class="text-xs text-right text-muted-foreground">Processing... 75%</p>
                        </div>
                     </div>
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </section>

      <!-- FAQ Section -->
      <section class="py-20">
        <div class="container mx-auto px-4 max-w-3xl">
          <div class="text-center mb-12">
            <h2 class="text-3xl font-bold tracking-tight mb-4">Frequently Asked Questions</h2>
          </div>
          
          <Accordion type="single" collapsible class="w-full">
            <AccordionItem value="item-1">
              <AccordionTrigger>What file formats do you support?</AccordionTrigger>
              <AccordionContent>
                We support CSV, Excel (XLS/XLSX), XML, and JSON formats out of the box. We also support custom delimiters and text qualifiers for CSV files.
              </AccordionContent>
            </AccordionItem>
            <AccordionItem value="item-2">
              <AccordionTrigger>Is there a limit on file size?</AccordionTrigger>
              <AccordionContent>
                Our standard plan supports files up to 500MB. Enterprise plans can handle files up to 5GB using our streaming upload technology.
              </AccordionContent>
            </AccordionItem>
            <AccordionItem value="item-3">
              <AccordionTrigger>Can I schedule recurring imports?</AccordionTrigger>
              <AccordionContent>
                Yes! You can set up pipeline schedules to fetch data from FTP/SFTP servers, URLs, or S3 buckets at defined intervals (hourly, daily, weekly).
              </AccordionContent>
            </AccordionItem>
             <AccordionItem value="item-4">
              <AccordionTrigger>How do you handle data privacy?</AccordionTrigger>
              <AccordionContent>
                Your data is encrypted in transit and at rest. We are SOC2 compliant and support GDPR data processing agreements. We act as a data processor and do not sell your data.
              </AccordionContent>
            </AccordionItem>
          </Accordion>
        </div>
      </section>

      <!-- CTA Section -->
      <section class="py-20 bg-primary text-primary-foreground">
        <div class="container mx-auto px-4 text-center">
          <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to streamline your data?</h2>
          <p class="text-lg md:text-xl text-primary-foreground/80 max-w-2xl mx-auto mb-8">
            Join 10,000+ developers who trust ImportPipeline for their critical data operations.
          </p>
          <Button size="lg" variant="secondary" class="h-14 px-10 text-lg rounded-full" as-child>
            <Link :href="route('register')">Get Started for Free</Link>
          </Button>
        </div>
      </section>
    </main>
    
    <footer class="border-t py-12 bg-background">
      <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-4 gap-8 mb-8">
            <div class="col-span-1 md:col-span-2 space-y-4">
                <div class="flex items-center gap-2 font-bold text-lg">
                    <div class="h-6 w-6 bg-primary rounded flex items-center justify-center text-primary-foreground">
                        <Database class="h-3 w-3" />
                    </div>
                    ImportPipeline
                </div>
                <p class="text-sm text-muted-foreground max-w-xs">
                    The complete platform for data ingestion, validation, and transformation.
                </p>
            </div>
            <div>
                <h3 class="font-semibold mb-3">Product</h3>
                <ul class="space-y-2 text-sm text-muted-foreground">
                    <li><a href="#" class="hover:text-foreground">Features</a></li>
                    <li><a href="#" class="hover:text-foreground">Pricing</a></li>
                    <li><a href="#" class="hover:text-foreground">Documentation</a></li>
                    <li><a href="#" class="hover:text-foreground">API</a></li>
                </ul>
            </div>
            <div>
                 <h3 class="font-semibold mb-3">Company</h3>
                <ul class="space-y-2 text-sm text-muted-foreground">
                    <li><a href="#" class="hover:text-foreground">About</a></li>
                    <li><a href="#" class="hover:text-foreground">Blog</a></li>
                    <li><a href="#" class="hover:text-foreground">Carreers</a></li>
                    <li><a href="#" class="hover:text-foreground">Contact</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t pt-8 flex flex-col md:flex-row justify-between items-center gap-6">
          <p class="text-sm text-muted-foreground">
            &copy; {{ new Date().getFullYear() }} ImportPipeline. All rights reserved.
          </p>
          <div class="flex items-center gap-4 text-sm text-muted-foreground">
            <a href="#" class="hover:text-foreground">Terms of Service</a>
            <a href="#" class="hover:text-foreground">Privacy Policy</a>
            <a href="#" class="hover:text-foreground">Cookie Policy</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
