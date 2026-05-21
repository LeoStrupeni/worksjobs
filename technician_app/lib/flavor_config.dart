class FlavorConfig {
  const FlavorConfig({
    required this.name,
    required this.pendingUploadsRetrySeconds,
    required this.pendingUploadsMaxBatch,
  });

  final String name;
  final int pendingUploadsRetrySeconds;
  final int pendingUploadsMaxBatch;
}

const FlavorConfig flavorConfigDev = FlavorConfig(
  name: 'dev',
  pendingUploadsRetrySeconds: 30,
  pendingUploadsMaxBatch: 5,
);

const FlavorConfig flavorConfigQa = FlavorConfig(
  name: 'qa',
  pendingUploadsRetrySeconds: 60,
  pendingUploadsMaxBatch: 5,
);

const FlavorConfig flavorConfigProd = FlavorConfig(
  name: 'prod',
  pendingUploadsRetrySeconds: 120,
  pendingUploadsMaxBatch: 5,
);
