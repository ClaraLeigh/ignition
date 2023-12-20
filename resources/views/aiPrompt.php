<?php /** @var \Spatie\Ignition\Solutions\OpenAi\OpenAiPromptViewModel $viewModel */ ?>

# Related Files
<?php echo $viewModel->relatedFiles() ?>

# Error Context

Line: <?php echo $viewModel->line() ?>

File:
<?php echo $viewModel->file() ?>

Snippet including line numbers:
<?php echo $viewModel->snippet() ?>

Exception class:
<?php echo $viewModel->exceptionClass() ?>

Exception message:
<?php echo $viewModel->exceptionMessage() ?>
